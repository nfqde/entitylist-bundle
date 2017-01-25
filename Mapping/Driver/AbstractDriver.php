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

use Nfq\Bundle\EntityListBundle\Mapping\EntityList;
use Nfq\Bundle\EntityListBundle\Mapping\Exception\MappingException;
use Nfq\Bundle\EntityListBundle\Mapping\MappingInformation;

/**
 * Class AbstractDriver.
 */
abstract class AbstractDriver implements DriverInterface
{
    /**
     * Returns array of sortable fields mappings.
     *
     * @param string $className
     *
     * @return array
     */
    public function getSortableFieldsMappings($className)
    {
        $mappingInformation = $this->getMappingInformation($className);

        $sortableFieldsMappings = [];
        foreach ($mappingInformation->getSortableFields() as $fieldName => $fieldData) {
            if (!is_array($fieldData)) {
                $fieldName = $fieldData;
                $fieldData = [
                    'target' => EntityList::FIELD_TARGET_DIRECT,
                ];
            }

            if (!isset($fieldData['target'])) {
                $fieldData['target'] = EntityList::FIELD_TARGET_DIRECT;
            }

            if (!isset($fieldData['name'])) {
                $fieldData['name'] = $fieldName;
            }

            $this->validateFieldData($fieldData);

            $sortableFieldsMappings[$fieldName] = $fieldData;
        }

        return $sortableFieldsMappings;
    }

    /**
     * Returns array of filterable fields mappings.
     *
     * @param string $className
     *
     * @return array
     */
    public function getFilterFieldsMappings($className)
    {
        $mappingInformation = $this->getMappingInformation($className);

        $filterFieldsMappings = [];
        foreach ($mappingInformation->getFilterFields() as $fieldName => $fieldData) {
            if (!is_array($fieldData)) {
                $fieldName = $fieldData;
                $fieldData = [
                    'target' => EntityList::FIELD_TARGET_DIRECT,
                ];
            }

            if (!isset($fieldData['target'])) {
                $fieldData['target'] = EntityList::FIELD_TARGET_DIRECT;
            }

            if (!isset($fieldData['filterField'])) {
                $fieldData['filterField'] = $fieldName;
            }

            if (!isset($fieldData['title'])) {
                $fieldData['title'] = $fieldName;
            }

            if (!isset($fieldData['globalSearch'])) {
                $fieldData['globalSearch'] = false;
            }

            if (!isset($fieldData['type'])) {
                $fieldData['type'] = 'text';
            }

            $this->validateFilterableFieldData($fieldData);

            $filterFieldsMappings[$fieldName] = $fieldData;
        }

        return $filterFieldsMappings;
    }

    /**
     * Returns array of search fields mappings.
     *
     * @param string $className
     *
     * @return array
     */
    public function getSearchFieldsMappings($className)
    {
        $mappingInformation = $this->getMappingInformation($className);

        $searchFieldsMappings = [];
        foreach ($mappingInformation->getSearchFields() as $fieldName => $fieldData) {
            if (!is_array($fieldData)) {
                $fieldName = $fieldData;
                $fieldData = [
                    'target' => EntityList::FIELD_TARGET_DIRECT,
                ];
            }

            $this->validateFieldData($fieldData);

            $searchFieldsMappings[$fieldName] = $fieldData;
        }

        // Merge with filters marked as "globalSearch"=true.
        foreach ($this->getFilterFieldsMappings($className) as $fieldName => $fieldData) {
            if ($fieldData['globalSearch'] && !isset($searchFieldsMappings[$fieldName])) {
                $searchFieldsMappings[$fieldName] = $fieldData;
            }
        }

        return $searchFieldsMappings;
    }

    /**
     * Returns default order by mappings.
     *
     * @param string $className
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getDefaultOrderMappings($className)
    {
        $mappingInformation = $this->getMappingInformation($className);
        $defaultOrderFields = [];
        foreach ($mappingInformation->getDefaultOrderFields() as $fieldName => $fieldData) {
            if (!is_array($fieldData)) {
                $fieldName = $fieldData;
                $fieldData = [
                    'target' => EntityList::FIELD_TARGET_DIRECT,
                    'direction' => 'ASC',
                ];
            }

            $this->validateFieldData($fieldData);

            if (!in_array($fieldData['direction'], ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(
                    sprintf('Direction "%s" is not acceptable', $fieldData['direction'])
                );
            }
            $defaultOrderFields[$fieldName] = $fieldData;
        }

        return $defaultOrderFields;
    }

    /**
     * Returns group by condition.
     *
     * @param string $className
     *
     * @return array
     */
    public function getGroupBy($className)
    {
        $mappingInformation = $this->getMappingInformation($className);

        return (string)$mappingInformation->getGroupBy();
    }

    /**
     * Returns entity's list mapping information by class name.
     *
     * @param string $className
     *
     * @return MappingInformation
     *
     * @throws \Exception
     */
    abstract protected function getMappingInformation($className);

    /**
     * Validates field metadata.
     *
     * @param array $fieldData
     *
     * @throws MappingException
     */
    protected function validateFieldData($fieldData)
    {
        if (!isset($fieldData['target'])) {
            throw new MappingException('Sortable field target is not set.');
        }

        if (!in_array($fieldData['target'], [EntityList::FIELD_TARGET_DIRECT, EntityList::FIELD_TARGET_RELATION])) {
            throw new MappingException(
                sprintf('Unknown sortable field target "%s"', $fieldData['target'])
            );
        }

        if ($fieldData['target'] == EntityList::FIELD_TARGET_RELATION
            && !isset($fieldData['joinField'], $fieldData['joinType'])
        ) {
            throw new MappingException(
                sprintf(
                    'Attributes "joinField" and "joinType" must be set to sortable field of target "%s" options.',
                    EntityList::FIELD_TARGET_RELATION
                )
            );
        }

        if ($fieldData['target'] == EntityList::FIELD_TARGET_RELATION
            && !in_array($fieldData['joinType'], ['leftJoin', 'rightJoin', 'innerJoin', 'join'])
        ) {
            throw new MappingException(
                sprintf('Unknown sortable field join type "%s"', $fieldData['joinType'])
            );
        }
    }

    /**
     * Validates filterable field metadata.
     *
     * @param array $fieldData
     *
     * @throws MappingException
     */
    protected function validateFilterableFieldData($fieldData)
    {
        $this->validateFieldData($fieldData);

        if (!isset($fieldData['operators'])) {
            throw new MappingException('Filterable field operators is not set.');
        }
    }
}
