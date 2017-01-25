<?php

/*
 * This file is part of the NfqEntityListBundle package.
 *
 * (c) 2017 .NFQ | Netzfrequenz GmbH <info@nfq.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\Bundle\EntityListBundle\Mapping\Metadata;

/**
 * Class ListMetadata.
 */
class ListMetadata
{
    /**
     * @var array
     */
    protected $sortFieldsMappings;

    /**
     * @var array
     */
    protected $filterFieldsMappings;

    /**
     * @var array
     */
    protected $searchFieldsMappings;

    /**
     * @var array
     */
    protected $defaultOrderMappings;

    /**
     * @var string
     */
    protected $groupBy;

    /**
     * Constructor.
     *
     * @param array       $sortFieldsMappings
     * @param array       $filterFieldsMappings
     * @param array       $searchFieldsMappings
     * @param array       $defaultOrderMappings
     * @param string|null $groupBy
     */
    public function __construct(
        array $sortFieldsMappings,
        array $filterFieldsMappings = [],
        array $searchFieldsMappings = [],
        array $defaultOrderMappings = [],
        $groupBy = null
    ) {
        $this->sortFieldsMappings = $sortFieldsMappings;
        $this->filterFieldsMappings = $filterFieldsMappings;
        $this->searchFieldsMappings = $searchFieldsMappings;
        $this->defaultOrderMappings = $defaultOrderMappings;
        $this->groupBy = $groupBy;
    }

    /**
     * Getter for sortFieldsMappings.
     *
     * @return array
     */
    public function getSortFieldsMappings()
    {
        return $this->sortFieldsMappings;
    }

    /**
     * Getter for filterFieldsMappings.
     *
     * @return array
     */
    public function getFilterFieldsMappings()
    {
        return $this->filterFieldsMappings;
    }

    /**
     * Getter for searchFieldsMappings.
     *
     * @return array
     */
    public function getSearchFieldsMappings()
    {
        return $this->searchFieldsMappings;
    }

    /**
     * Getter for defaultOrderMappings.
     *
     * @return array
     */
    public function getDefaultOrderMappings()
    {
        return $this->defaultOrderMappings;
    }

    /**
     * Getter for groupBy.
     *
     * @return string
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }
}
