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
 * Class EntityList.
 *
 * @Annotation
 * @Target("CLASS")
 */
class EntityList
{
    const FIELD_TARGET_DIRECT = 'direct';
    const FIELD_TARGET_RELATION = 'relation';

    /**
     * @var array
     */
    private $sortableFields = [];

    /**
     * @var array
     */
    private $filterFields = [];

    /**
     * @var array
     */
    private $searchFields = [];

    /**
     * @var array
     */
    private $defaultOrderFields = [];

    /**
     * @var string
     */
    private $groupBy;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (isset($options['sortableFields'])) {
            $this->sortableFields = $options['sortableFields'];
        }

        if (isset($options['filterFields'])) {
            $this->filterFields = $options['filterFields'];
        }

        if (isset($options['searchFields'])) {
            $this->searchFields = $options['searchFields'];
        }

        if (isset($options['defaultOrderFields'])) {
            $this->defaultOrderFields = $options['defaultOrderFields'];
        }

        if (isset($options['groupBy'])) {
            $this->groupBy = $options['groupBy'];
        }
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
