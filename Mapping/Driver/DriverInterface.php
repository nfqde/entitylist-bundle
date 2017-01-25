<?php

/*
 * This file is part of the NfqEntityListBundle package.
 *
 * (c) 2017 .NFQ | Netzfrequenz GmbH <info@nfq.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\Bundle\EntityListBundle\Mapping\Driver;

/**
 * Interface DriverInterface.
 */
interface DriverInterface
{
    /**
     * Returns array of sortable fields mappings.
     *
     * @param string $className
     *
     * @return array
     */
    public function getSortableFieldsMappings($className);

    /**
     * Returns array of filterable fields mappings.
     *
     * @param string $className
     *
     * @return array
     */
    public function getFilterFieldsMappings($className);

    /**
     * Returns array of search fields mappings.
     *
     * @param string $className
     *
     * @return array
     */
    public function getSearchFieldsMappings($className);

    /**
     * Returns default order by condition.
     *
     * @param string $className
     *
     * @return array
     */
    public function getDefaultOrderMappings($className);

    /**
     * Returns group by condition.
     *
     * @param string $className
     *
     * @return array
     */
    public function getGroupBy($className);
}
