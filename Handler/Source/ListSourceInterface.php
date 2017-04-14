<?php

namespace Nfq\Bundle\EntityListBundle\Handler\Source;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface ListSourceInterface.
 */
interface ListSourceInterface
{
    const FILTERS_PARAM_NAME = 'filters';
    const SEARCH_PARAM_NAME = 'search';
    const ORDER_BY_PARAM_NAME = 'order_by';
    const PAGE_NR_PARAM_NAME = 'page';
    const PAGE_LIMIT_PARAM_NAME = 'page_limit';
    const DEFAULT_PAGE_LIMIT = 10;
    const ROOT_ENTITY_ALIAS = 'e';
    const SEARCH_TERM_SEPARATOR = '__AND__';

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
    public function processSource(Request $request);

    /**
     * Returns found list items count.
     *
     * @param Request $request
     *
     * @return int
     */
    public function getItemsCount(Request $request);

    /**
     * Returns array of sortable fields.
     *
     * @return array
     */
    public function getSortableFields();

    /**
     * Returns array of filter fields.
     *
     * @return array
     */
    public function getFilterableFields();
}
