<?php

namespace Nfq\Bundle\EntityListBundle\Handler\Source;

use Nfq\Bundle\EntityListBundle\Mapping\Metadata\ListMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ArrayListSource.
 */
class ArrayListSource extends AbstractListSource
{
    /**
     * @var ListMetadata
     */
    protected $listMetadata;

    /**
     * @var array
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param ListMetadata $listMetadata
     * @param array        $listHandlerConfig
     * @param array        $data
     */
    public function __construct(ListMetadata $listMetadata, array $listHandlerConfig, array $data)
    {
        parent::__construct($listMetadata, $listHandlerConfig);
        $this->data = $data;
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
        $data = $this->data;
        $this->addFilterConditions($data, $request);
        $this->addSearchConditions($data, $request);
        $this->addOrderBy($data, $request);
        $this->addPagination($data, $request);

        return $data;
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
        $data = $this->data;
        $this->addFilterConditions($data, $request);
        $this->addSearchConditions($data, $request);

        return count($data);
    }

    /**
     * Adds pagination to data array.
     *
     * @param array   $data
     * @param Request $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addFilterConditions(array &$data, Request $request)
    {
        // TODO: not implemented yet.
    }

    /**
     * Adds pagination to data array.
     *
     * @param array   $data
     * @param Request $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addSearchConditions(array &$data, Request $request)
    {
        $filtersData = $request->query->get($this->listHandlerConfig['filters_param_name'], []);
        if (!isset($filtersData[$this->listHandlerConfig['search_param_name']])) {
            return;
        }

        if (!$this->getSearchFieldsMappings()) {
            throw new \InvalidArgumentException('This list has not search feature');
        }

        $searchValue = (string)$filtersData[self::SEARCH_PARAM_NAME];

        $data = array_filter(
            $data,
            function ($row) use ($searchValue) {
                $match = false;
                foreach ($this->getSearchFieldsMappings() as $fieldName => $fieldData) {
                    if (strpos($fieldName, '.') !== false) {
                        list($assocField, $sortField) = explode('.', $fieldName);

                        $match = stripos($row[$assocField][$sortField], $searchValue) !== false;
                        if ($match) {
                            break;
                        }

                        continue;
                    }

                    $match = stripos($row[$fieldName], $searchValue) !== false;
                    if ($match) {
                        break;
                    }
                }

                return $match;
            }
        );
    }

    /**
     * Adds pagination to data array.
     *
     * @param array   $data
     * @param Request $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addPagination(array &$data, Request $request)
    {
        $paginationData = $this->extractPagination($request);
        $pageNr = $paginationData['pageNr'];
        $pageLimit = $paginationData['pageLimit'];

        if ($pageLimit || $pageNr > 1) {
            $data = array_slice($data, ($pageNr - 1) * $pageLimit, $pageLimit);
        }
    }

    /**
     * Adds ordering to data array.
     *
     * @param array   $data
     * @param Request $request
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function addOrderBy(array &$data, Request $request)
    {
        $orderBy = $this->extractOrderBy($request);
        if (!count($orderBy)) {
            return;
        }

        $arguments = [];
        $argumentsIndex = 0;
        // Obtain a list of columns.
        foreach ($orderBy as $orderIndex => $orderByData) {
            if (!in_array($orderByData['field'], $this->getSortableFields())) {
                throw new \InvalidArgumentException(
                    sprintf('Field "%s" is not sortable', $orderByData['field'])
                );
            }

            $field = $orderByData['field'];
            $direction = SORT_ASC;
            if (isset($orderByData['direction'])) {
                $direction = $orderByData['direction'];
            }

            if (!in_array($direction, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(
                    sprintf('Order direction "%s" is not acceptable. It should be "ASC" or "DESC"', $direction)
                );
            }

            foreach ($data as $key => $row) {
                if (strpos($orderByData['field'], '.') !== false) {
                    list($assocField, $sortField) = explode('.', $orderByData['field']);
                    $arguments[$argumentsIndex][$key] = $row[$assocField][$sortField];

                    continue;
                }

                $arguments[$argumentsIndex][$key] = $row[$field];
            }

            $argumentsIndex++;
            $arguments[$argumentsIndex] = $direction == 'ASC' ? SORT_ASC : SORT_DESC;
            $argumentsIndex++;
            $arguments[$argumentsIndex] = SORT_NATURAL;
            $argumentsIndex++;
        }

        $arguments[$argumentsIndex] = &$data;

        call_user_func_array('array_multisort', array_values($arguments));
    }
}
