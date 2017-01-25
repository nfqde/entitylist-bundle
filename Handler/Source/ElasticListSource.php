<?php

namespace Nfq\Bundle\EntityListBundle\Handler\Source;

use Elastica\Query;
use Elastica\Filter as ElasticFilter;
use Elastica\Type as ElasticaType;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\PaginatorInterface;
use Nfq\Bundle\EntityListBundle\Mapping\EntityList;
use Nfq\Bundle\EntityListBundle\Mapping\Metadata\ListMetadata;
use Nfq\Bundle\EntityListBundle\ValueObject\Filter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ElasticListSource.
 */
class ElasticListSource extends AbstractListSource
{
    /**
     * @var PaginatedFinderInterface
     */
    protected $finder;

    /**
     * @var ElasticaType
     */
    protected $indexType;

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * Constructor.
     *
     * @param ListMetadata             $listMetadata
     * @param array                    $listHandlerConfig
     * @param PaginatedFinderInterface $finder
     * @param ElasticaType             $indexType
     * @param PaginatorInterface       $paginator
     */
    public function __construct(
        ListMetadata $listMetadata,
        array $listHandlerConfig,
        PaginatedFinderInterface $finder,
        ElasticaType $indexType,
        PaginatorInterface $paginator
    ) {
        parent::__construct($listMetadata, $listHandlerConfig);
        $this->finder = $finder;
        $this->indexType = $indexType;
        $this->paginator = $paginator;
    }

    /**
     * Handles entity list data source.
     *
     * Applies:
     *  - sorting
     *  - filters
     *  - search
     *  - grouping
     *
     * @param Request $request
     *
     * @return array
     */
    public function processSource(Request $request)
    {
        $query = $this->getSearchQuery($request);

        $paginationData = $this->extractPagination($request);
        $pageNr = $paginationData['pageNr'];
        $pageLimit = $paginationData['pageLimit'];
        if (!$pageNr) {
            $pageNr = 1;
        }

        if (!$pageLimit) {
            $pageLimit = $this->getItemsCount($request);
        }
        $this->addOrderBy($query, $request);

        $results = $this->finder->createPaginatorAdapter($query);

        /** @var SlidingPagination $pagination */
        $pagination = $this->paginator->paginate($results, $pageNr, $pageLimit);

        return $pagination->getItems();
    }

    /**
     * Returns found list items count.
     *
     * @param Request $request
     *
     * @return int
     */
    public function getItemsCount(Request $request)
    {
        $query = $this->getSearchQuery($request);

        return $this->indexType->count($query);
    }

    /**
     * Returns query for search.
     *
     * @param Request $request
     *
     * @return Query
     */
    protected function getSearchQuery(Request $request)
    {
        $query = new Query();
        $this->addSearchAndFiltersConditions($query, $request);

        return $query;
    }

    /**
     * Adds order by condition to query.
     *
     * @param Query   $query
     * @param Request $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addOrderBy(Query $query, Request $request)
    {
        $orderBy = $this->extractOrderBy($request);
        if (!count($orderBy)) {
            return;
        }

        $sortData = [];
        foreach ($orderBy as $orderByData) {
            if (!in_array($orderByData['field'], $this->getSortableFields())) {
                throw new \InvalidArgumentException(
                    sprintf('Field "%s" is not sortable', $orderByData['field'])
                );
            }

            $field = $orderByData['field'];
            $direction = 'ASC';
            if (isset($orderByData['direction'])) {
                $direction = $orderByData['direction'];
            }

            if (!in_array($direction, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(
                    sprintf('Order direction "%s" is not acceptable. It should be "ASC" or "DESC"', $direction)
                );
            }

            $orderFieldConfig = $this->getSortFieldsMappings()[$field];
            $orderField = $orderFieldConfig['name'];

            $sortData[$orderField] = ['order' => strtolower($direction)];
        }

        $query->addSort($sortData);
    }

    /**
     * Adds search conditions to query.
     *
     * @param Query   $query
     * @param Request $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addSearchAndFiltersConditions(Query $query, Request $request)
    {
        $filtersData = $request->query->get($this->listHandlerConfig['filters_param_name'], []);

        $fullFilter = new Query\Bool();
        $globalSearchFilter = $this->getGlobalSearchQueries($filtersData);

        // Filters.
        $containsFilters = [];
        if ($globalSearchFilter) {
            $containsFilters[] = $globalSearchFilter;
        }
        $notContainsFilters = [];
        $fieldsFilter = new ElasticFilter\BoolFilter();

        $filters = $this->extractFilters($request);
        if ($filters) {
            foreach ($filters as $filter) {
                $this->addFieldFilter($fieldsFilter, $filter, $containsFilters, $notContainsFilters);
            }
        }

        if ($containsFilters) {
            $fullFilter->addMust($containsFilters);
        }

        // TODO: change to MUST missing or mustNot contain?
        if ($notContainsFilters) {
            $fullFilter->addMustNot($notContainsFilters);
        }

        if ($fieldsFilter->toArray()) {
            $filteredQuery = new Query\Filtered($fullFilter, $fieldsFilter);
            $query->setQuery($filteredQuery);
        } else if ($fullFilter->getParams()) {
            $query->setQuery($fullFilter);
        }
    }

    /**
     * Add filter for specific field.
     *
     * This function also adds filters to contains and notContains filter arrays which are passed by reference.
     *
     * @param ElasticFilter\BoolFilter $fieldsFilter
     * @param Filter                   $filter
     * @param array                    $containsFilters
     * @param array                    $notContainsFilters
     */
    protected function addFieldFilter(
        ElasticFilter\BoolFilter $fieldsFilter,
        Filter $filter,
        array &$containsFilters,
        array &$notContainsFilters
    ) {
        $filterFieldsMappings = $this->getFilterFieldsMappings();
        $this->validateFilter($filter, $filterFieldsMappings);

        $filterMetadata = $filterFieldsMappings[$filter->getField()];
        $operator = $this->normalizeOperator($filter->getOperator());
        $value = $this->normalizeValue($operator, $filter->getValue()['from'], $filterMetadata);

        if ($value === '' || $value === null) {
            return;
        }

        $fieldName = $filterMetadata['filterField'];
        switch ($operator) {
            case Filter::OPERATOR_EQ:
                $fieldsFilter->addMust($this->getEqualFilter($value, $fieldName, $filterMetadata));
                break;
            case Filter::OPERATOR_NEQ:
                $fieldsFilter->addMust($this->getNotEqualFilter($value, $fieldName, $filterMetadata));
                break;
            case Filter::OPERATOR_LT:
            case Filter::OPERATOR_LTE:
            case Filter::OPERATOR_GT:
            case Filter::OPERATOR_GTE:
                $fieldsFilter->addMust($this->getRangeFilter($value, $operator, $fieldName, $filterMetadata));
                break;
            case Filter::OPERATOR_LIKE:
                $containsFilters[] = $this->getContainsFilter($value, $fieldName, $filterMetadata);
                break;
            case Filter::OPERATOR_NLIKE:
                $notContainsFilters[] = $this->getContainsFilter($value, $fieldName, $filterMetadata);
                break;
            default:
                // Do nothing.
                break;
        }
    }

    /**
     * Returns contains filter for EQUALS operator depending on field target.
     *
     * @param mixed  $value
     * @param string $fieldName
     * @param array  $filterMetadata
     *
     * @return ElasticFilter\AbstractFilter
     */
    protected function getContainsFilter($value, $fieldName, $filterMetadata)
    {
        if ($filterMetadata['target'] == EntityList::FIELD_TARGET_RELATION) {
            $path = explode('.', $fieldName)[0];
            $nestedFilter = new ElasticFilter\Nested();
            $nestedFilter->setPath($path);
            $nestedFilter->setQuery($this->createMultiMatchQuery([$fieldName], $value));

            return $nestedFilter;
        }

        return $this->createMultiMatchQuery([$fieldName], $value);
    }

    /**
     * Returns filter for EQUALS operator depending on field target.
     *
     * @param mixed  $value
     * @param string $fieldName
     * @param array  $filterMetadata
     *
     * @return ElasticFilter\AbstractFilter
     */
    protected function getEqualFilter($value, $fieldName, $filterMetadata)
    {
        if ($filterMetadata['target'] == EntityList::FIELD_TARGET_RELATION) {
            $path = explode('.', $fieldName)[0];
            $nestedFilter = new ElasticFilter\Nested();
            $nestedFilter->setPath($path);
            $nestedFilter->setFilter($this->getEqualFilterByType($value, $fieldName, $filterMetadata));

            return $nestedFilter;
        }

        return $this->getEqualFilterByType($value, $fieldName, $filterMetadata);
    }

    /**
     * Returns filter for NOT EQUALS operator depending on field target.
     *
     * Missing or mustNot.
     *
     * @param mixed  $value
     * @param string $fieldName
     * @param array  $filterMetadata
     *
     * @return ElasticFilter\AbstractFilter
     */
    protected function getNotEqualFilter($value, $fieldName, $filterMetadata)
    {
        $termFilter = new ElasticFilter\Term([sprintf('%s.raw', $fieldName) => $value]);
        $missingFieldFilter = new ElasticFilter\Missing(sprintf('%s.raw', $fieldName));
        $mustNot = new ElasticFilter\Bool();
        $mustNot->addMustNot($termFilter);
        $missingOrNotEqual = new ElasticFilter\Bool();
        $missingOrNotEqual->addShould([$missingFieldFilter, $mustNot]);

        // Missing nested or missing field of nested or field of nested not equal.
        if ($filterMetadata['target'] == EntityList::FIELD_TARGET_RELATION) {
            $path = explode('.', $fieldName)[0];
            $nestedFieldFilter = new ElasticFilter\Nested();
            $nestedFieldFilter->setPath($path);

            $missingNestedFieldFilter = new ElasticFilter\BoolNot(
                clone $nestedFieldFilter->setFilter(new ElasticFilter\MatchAll())
            );
            $rootFilter = new ElasticFilter\BoolFilter();
            $rootFilter->addShould(
                [$missingNestedFieldFilter, clone $nestedFieldFilter->setFilter($missingOrNotEqual)]
            );

            return $rootFilter;
        }

        return $missingOrNotEqual;
    }

    /**
     * Returns range filter depending on field target.
     *
     * @param mixed  $value
     * @param string $operator
     * @param string $fieldName
     * @param array  $filterMetadata
     *
     * @return ElasticFilter\AbstractFilter
     */
    protected function getRangeFilter($value, $operator, $fieldName, $filterMetadata)
    {
        $rangeFilter = new ElasticFilter\Range(sprintf('%s.raw', $fieldName), [$operator => $value]);
        if ($filterMetadata['target'] == EntityList::FIELD_TARGET_RELATION) {
            $path = explode('.', $fieldName)[0];
            $nestedFilter = new ElasticFilter\Nested();
            $nestedFilter->setPath($path);
            $nestedFilter->setFilter($rangeFilter);

            return $nestedFilter;
        }

        return $rangeFilter;
    }

    /**
     * Returns filter for EQUALS operator depending on filter type.
     *
     * @param mixed  $value
     * @param string $fieldName
     * @param array  $filterMetadata
     *
     * @return ElasticFilter\AbstractFilter
     */
    protected function getEqualFilterByType($value, $fieldName, $filterMetadata)
    {
        $termFilter = new ElasticFilter\Term([sprintf('%s.raw', $fieldName) => $value]);
        switch ($filterMetadata['type']) {
            case 'boolean':
                if (!$value) {
                    // Null or false.
                    $filter = new ElasticFilter\Bool();
                    $filter->addShould([new ElasticFilter\Missing(sprintf('%s.raw', $fieldName)), $termFilter]);

                    return $filter;
                }

                return $termFilter;
            default:
                // Do nothing.
                break;
        }

        return $termFilter;
    }

    /**
     * Create multi match query.
     *
     * @param array  $fields
     * @param string $filterValue
     *
     * @return Query\MultiMatch
     */
    protected function createMultiMatchQuery(array $fields, $filterValue)
    {
        $multiMatch = new Query\MultiMatch();
        $multiMatch->setFields($fields);
        $multiMatch->setQuery($filterValue);
        $multiMatch->setOperator('and');
        $multiMatch->setAnalyzer('whitespace_lowercase');

        return $multiMatch;
    }

    /**
     * Return array of global search queries.
     *
     * @param array $filtersData
     *
     * @return Query\Bool|null
     *
     * @throws \InvalidArgumentException
     */
    protected function getGlobalSearchQueries($filtersData)
    {
        if (!isset($filtersData[$this->listHandlerConfig['search_param_name']])) {
            return null;
        }

        if (!$this->getSearchFieldsMappings()) {
            throw new \InvalidArgumentException('This list has not search feature');
        }

        $searchValue = (string)$filtersData[$this->listHandlerConfig['search_param_name']];
        if ($searchValue === '' || $searchValue === null) {
            return null;
        }

        $relationFields = [];
        $directFields = [];
        foreach ($this->getSearchFieldsMappings() as $fieldName => $fieldMapping) {
            if ($fieldMapping['target'] == EntityList::FIELD_TARGET_DIRECT) {
                $directFields[$fieldName] = $fieldMapping;
                continue;
            }

            $path = explode('.', $fieldName)[0];
            $relationFields[$path][$fieldName] = $fieldMapping;
        }

        $searchQueries = [];
        // Direct fields.
        $multiMatch = $this->createMultiMatchQuery(array_keys($directFields), $searchValue);
        $searchQueries[] = $multiMatch;

        // Relations (nested) fields.
        foreach ($relationFields as $path => $fields) {
            $multiMatch = $this->createMultiMatchQuery(array_keys($fields), $searchValue);

            $nestedQuery = new Query\Nested();
            $nestedQuery->setPath($path);
            $nestedQuery->setQuery($multiMatch);

            $searchQueries[] = $nestedQuery;
        }

        $globalSearchFilter = new Query\Bool();
        $globalSearchFilter->addShould($searchQueries);

        return $globalSearchFilter;
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
        if (!in_array($operator, Filter::getElasticAcceptableOperators())) {
            throw new \InvalidArgumentException(
                sprintf('Unknown filter operator "%s"', $operator)
            );
        }

        return $operator;
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

        // TODO: do we need to do some actions depending on operator?

        return $value;
    }
}
