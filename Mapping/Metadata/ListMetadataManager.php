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

use Nfq\Bundle\EntityListBundle\Mapping\Driver\DriverInterface;

/**
 * Class ListMetadataManager.
 */
class ListMetadataManager
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * Constructor.
     *
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Returns list metadata for given entity.
     *
     * @param string $className
     *
     * @return ListMetadata
     */
    public function getListMetadata($className)
    {
        $sortFieldsMappings = $this->driver->getSortableFieldsMappings($className);
        $filterFieldsMappings = $this->driver->getFilterFieldsMappings($className);
        $searchFieldsMappings = $this->driver->getSearchFieldsMappings($className);
        $defaultOrderMappings = $this->driver->getDefaultOrderMappings($className);
        $groupBy = $this->driver->getGroupBy($className);

        $metadata = new ListMetadata(
            $sortFieldsMappings,
            $filterFieldsMappings,
            $searchFieldsMappings,
            $defaultOrderMappings,
            $groupBy
        );

        return $metadata;
    }
}
