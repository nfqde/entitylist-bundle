<?php

/*
 * This file is part of the NfqEntityListBundle package.
 *
 * (c) 2017 .NFQ | Netzfrequenz GmbH <info@nfq.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\Bundle\EntityListBundle\Mapping;

/**
 * Class MappingInformation.
 */
class MappingInformation
{
    /**
     * @var array
     */
    private $sortableFields;

    /**
     * @var array
     */
    private $filterFields;

    /**
     * @var array
     */
    private $searchFields;

    /**
     * @var array
     */
    private $defaultOrderFields;

    /**
     * @var string
     */
    private $groupBy;

    /**
     * Constructor.
     *
     * @param array  $sortableFields
     * @param array  $filterFields
     * @param array  $searchFields
     * @param array  $defaultOrderFields
     * @param string $groupBy
     */
    public function __construct(
        array $sortableFields,
        array $filterFields,
        array $searchFields,
        array $defaultOrderFields,
        $groupBy
    ) {
        $this->sortableFields = $sortableFields;
        $this->filterFields = $filterFields;
        $this->searchFields = $searchFields;
        $this->defaultOrderFields = $defaultOrderFields;
        $this->groupBy = $groupBy;
    }

    /**
     * Getter for sortableFields.
     *
     * @return array
     */
    public function getSortableFields()
    {
        return $this->sortableFields;
    }

    /**
     * Getter for filterFields.
     *
     * @return array
     */
    public function getFilterFields()
    {
        return $this->filterFields;
    }

    /**
     * Getter for searchFields.
     *
     * @return array
     */
    public function getSearchFields()
    {
        return $this->searchFields;
    }

    /**
     * Getter for defaultOrderFields.
     *
     * @return array
     */
    public function getDefaultOrderFields()
    {
        return $this->defaultOrderFields;
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
