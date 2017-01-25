<?php

namespace Nfq\Bundle\EntityListBundle\Handler\Source;

use Nfq\Bundle\EntityListBundle\Mapping\Metadata\ListMetadata;
use Nfq\Bundle\EntityListBundle\ValueObject\Filter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AbstractListSource.
 */
abstract class AbstractListSource implements ListSourceInterface
{
    /**
     * @var ListMetadata
     */
    protected $listMetadata;

    /**
     * @var array
     */
    protected $listHandlerConfig;

    /**
     * Constructor.
     *
     * @param ListMetadata $listMetadata
     * @param array        $listHandlerConfig
     */
    public function __construct(ListMetadata $listMetadata, array $listHandlerConfig)
    {
        $this->listMetadata = $listMetadata;
        $this->listHandlerConfig = $listHandlerConfig;
    }

    /**
     * Returns array of sortable fields.
     *
     * @return array
     */
    public function getSortableFields()
    {
        return array_unique(
            array_merge(
                array_keys($this->listMetadata->getSortFieldsMappings()),
                array_keys($this->listMetadata->getDefaultOrderMappings())
            )
        );
    }

    /**
     * Returns array of filter fields with possible operators.
     *
     * @return array
     */
    public function getFilterableFields()
    {
        $filterableFields = [];
        foreach ($this->getFilterFieldsMappings() as $fieldName => $fieldMetadata) {
            $filterableFields[] = [
                'field' => $fieldName,
                'operators' => $fieldMetadata['operators'],
                'title' => $fieldMetadata['title'],
            ];
        }

        return $filterableFields;
    }

    /**
     * Extracts filters from request.
     *
     * @param Request $request
     *
     * @return Filter[]
     *
     * @throws \InvalidArgumentException
     */
    protected function extractFilters(Request $request)
    {
        $filters = [];
        $filtersData = $request->query->get($this->listHandlerConfig['filters_param_name'], []);
        // Unset search, because search is handled in other function.
        if (isset($filtersData[$this->listHandlerConfig['search_param_name']])) {
            unset($filtersData[$this->listHandlerConfig['search_param_name']]);
        }

        foreach ($filtersData as $data) {
            if (!isset($data['field'], $data['operator'], $data['value']['from'])) {
                throw new \InvalidArgumentException('Field, operator and value are required for filter');
            }

            $filters[] = new Filter($data['field'], $data['operator'], $data['value']);
        }

        return $filters;
    }

    /**
     * Extracts pagination data from request.
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function extractPagination(Request $request)
    {
        $pageNr = $request->get($this->listHandlerConfig['page_nr_param_name'], 1);
        $pageLimit = $request->get($this->listHandlerConfig['page_limit_param_name']);

        if (!is_numeric($pageNr)) {
            throw new \InvalidArgumentException(
                sprintf('Page number must be numeric, "%s" given', $pageNr)
            );
        }

        if ($pageLimit && !is_numeric($pageLimit)) {
            throw new \InvalidArgumentException(
                sprintf('Page limit must be numeric, "%s" given', $pageLimit)
            );
        }

        if ($pageNr > 1 && !$pageLimit) {
            $pageLimit = $this->listHandlerConfig['default_page_limit'];
        }

        return [
            'pageNr' => $pageNr,
            'pageLimit' => $pageLimit,
        ];
    }

    /**
     * Extracts order by data from request or return default order by if it is configured.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function extractOrderBy(Request $request)
    {
        $orderByData = $request->get($this->listHandlerConfig['sort_param_name'], []);
        if (isset($orderByData['field'])) {
            return [$orderByData];
        }

        return $this->getDefaultOrderBy();
    }

    /**
     * Validates filter.
     *
     * @param Filter $filter
     * @param array  $filterFieldsMappings
     *
     * @throws \InvalidArgumentException
     */
    protected function validateFilter(Filter $filter, $filterFieldsMappings)
    {
        // Field should be filterable.
        $field = $filter->getField();
        $filterFieldConfig = $this->getFilterableFieldConfig($field);
        if (!$filterFieldConfig) {
            throw new \InvalidArgumentException(sprintf('Field "%s" is not filterable', $field));
        }

        $normalizedOperator = $this->normalizeOperator($filter->getOperator());

        // Normalized operator should be one of known operators.
        $filterFieldMapping = $filterFieldsMappings[$field];
        $acceptableOperators = $filterFieldMapping['operators'];
        if (!in_array($normalizedOperator, $acceptableOperators)) {
            throw new \InvalidArgumentException(
                sprintf('Operator "%s" is not acceptable for filter field "%s"', $normalizedOperator, $field)
            );
        }

        // Between filter must have 2 values.
        $filterValue = $filter->getValue();
        if ($normalizedOperator == Filter::OPERATOR_BTW && !isset($filterValue['from'], $filterValue['to'])) {
            throw new \InvalidArgumentException('From and to values must be given for BETWEEN filter operator');
        }
    }

    /**
     * Returns filterable field config if given field is filterable.
     *
     * @param string $field
     *
     * @return array|null
     */
    protected function getFilterableFieldConfig($field)
    {
        $config = null;
        foreach ($this->getFilterableFields() as $fieldConfig) {
            if ($fieldConfig['field'] == $field) {
                $config = $fieldConfig;
                break;
            }
        }

        return $config;
    }

    /**
     * Returns default order by.
     *
     * @return array
     */
    protected function getDefaultOrderBy()
    {
        $defaultOrderBy = [];
        foreach ($this->listMetadata->getDefaultOrderMappings() as $fieldName => $fieldData) {
            $defaultOrderBy[] = [
                'field' => $fieldName,
                'direction' => $fieldData['direction'],
            ];
        }

        return $defaultOrderBy;
    }

    /**
     * Normalizes filter operator.
     *
     * @param string $operator
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function normalizeOperator($operator)
    {
        if (!in_array($operator, Filter::getAcceptableOperators())) {
            throw new \InvalidArgumentException(
                sprintf('Unknown filter operator "%s"', $operator)
            );
        }

        switch ($operator) {
            case Filter::OPERATOR_LIKE:
            case Filter::OPERATOR_LLIKE:
            case Filter::OPERATOR_RLIKE:
            case Filter::OPERATOR_NLIKE:
                return 'like';
            default:
                return $operator;
        }
    }

    /**
     * Normalizes filter value.
     *
     * @param string $operator
     * @param string $value
     * @param array  $filterMetadata
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function normalizeValue($operator, $value, $filterMetadata = [])
    {
        if (!in_array($operator, [Filter::OPERATOR_IN, Filter::OPERATOR_NOT_IN]) && is_array($value)) {
            throw new \InvalidArgumentException('Filer value should be scalar, array given.');
        }

        $value = $this->formatFilterValue($value, $filterMetadata);

        switch ($operator) {
            case Filter::OPERATOR_LIKE:
            case Filter::OPERATOR_NLIKE:
                return "%$value%";
            case Filter::OPERATOR_LLIKE:
                return "%$value";
            case Filter::OPERATOR_RLIKE:
                return "$value%";
            case Filter::OPERATOR_IN:
            case Filter::OPERATOR_NOT_IN:
                if (strpos($value, ',') !== false) {
                    return explode(',', $value);
                }

                if (!is_array($value)) {
                    $value = [$value];
                }

                return $value;
            default:
                return $value;
        }
    }

    /**
     * Formats filter value by filter type and extra params.
     *
     * @param string $value
     * @param array  $filterMetadata
     *
     * @return string
     */
    protected function formatFilterValue($value, $filterMetadata = [])
    {
        if ($value === null || !$filterMetadata) {
            return $value;
        }

        if (in_array($filterMetadata['type'], ['date', 'datetime']) && $filterMetadata['format']) {
            $value = (new \DateTime($value))->format($filterMetadata['format']);
        }

        if ($filterMetadata['type'] == 'boolean') {
            return boolval($value);
        }

        return strtolower($value);
    }

    /**
     * Returns filter fields mappings.
     *
     * @return array
     */
    protected function getFilterFieldsMappings()
    {
        return $this->listMetadata->getFilterFieldsMappings();
    }

    /**
     * Returns search fields mappings.
     *
     * @return array
     */
    protected function getSearchFieldsMappings()
    {
        return $this->listMetadata->getSearchFieldsMappings();
    }

    /**
     * Returns sort fields mappings.
     *
     * @return array
     */
    protected function getSortFieldsMappings()
    {
        return $this->listMetadata->getSortFieldsMappings();
    }
}
