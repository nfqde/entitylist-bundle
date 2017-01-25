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

use Doctrine\Common\Annotations\Reader;
use Nfq\Bundle\EntityListBundle\Mapping\EntityList;
use Nfq\Bundle\EntityListBundle\Mapping\MappingInformation;

/**
 * Class AnnotationDriver.
 */
class AnnotationDriver extends AbstractDriver
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * Constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
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
    protected function getMappingInformation($className)
    {
        $reflectionClass = $this->getReflectionClass($className);

        /** @var EntityList $annotations */
        $annotations = $this->reader->getClassAnnotation($reflectionClass, EntityList::class);
        if (!$annotations) {
            throw new \Exception(sprintf('Missing entity list mapping information for class "%s"', $className));
        }

        return new MappingInformation(
            $annotations->getSortableFields(),
            $annotations->getFilterFields(),
            $annotations->getSearchFields(),
            $annotations->getDefaultOrderFields(),
            $annotations->getGroupBy()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getReflectionClass($className)
    {
        if (!$this->reflectionClass) {
            $this->reflectionClass = new \ReflectionClass($className);
        }

        return $this->reflectionClass;
    }
}
