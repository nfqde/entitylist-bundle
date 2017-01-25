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

use Nfq\Bundle\EntityListBundle\Mapping\MappingInformation;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;

/**
 * Class YmlDriver.
 */
class YmlDriver extends AbstractDriver
{
    /**
     * @var string
     */
    protected $bundlesDirectory;

    /**
     * Constructor.
     *
     * @param string $bundlesDirectory
     */
    public function __construct($bundlesDirectory)
    {
        $this->bundlesDirectory = $bundlesDirectory;
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
        $mappingFile = $this->getMappingFilePath($className);
        if (!$mappingFile) {
            throw new \Exception(sprintf('Missing entity list mapping information for class "%s"', $className));
        }

        $ymlParser = new Parser();

        $mappings = $ymlParser->parse(file_get_contents($mappingFile));
        if (!$mappings) {
            throw new \Exception(sprintf('Missing entity list mapping information for class "%s"', $className));
        }

        return new MappingInformation(
            isset($mappings['sortableFields']) ? $mappings['sortableFields'] : [],
            isset($mappings['filterFields']) ? $mappings['filterFields'] : [],
            isset($mappings['searchFields']) ? $mappings['searchFields'] : [],
            isset($mappings['defaultOrderFields']) ? $mappings['defaultOrderFields'] : [],
            isset($mappings['groupBy']) ? $mappings['groupBy'] : null
        );
    }

    /**
     * Returns path to mapping info file.
     *
     * @param string $className
     *
     * @return string|null
     */
    protected function getMappingFilePath($className)
    {
        $parsedNamespace = explode('\\', $className);
        $mappingFileName = strtolower(end($parsedNamespace) . '.yml');

        $bundleName = current(
            array_filter(
                $parsedNamespace,
                function ($value) {
                    return strpos($value, 'Bundle') > 0;
                }
            )
        );

        $directory = sprintf('%s/%s/Resources/list_mapping', $this->bundlesDirectory, $bundleName);
        if (!file_exists($directory)) {
            return null;
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in(sprintf('%s/%s/Resources/list_mapping', $this->bundlesDirectory, $bundleName))
            ->name($mappingFileName);

        if (!$finder->count()) {
            return null;
        }

        $filesArray = iterator_to_array($finder->getIterator());
        $file = current($filesArray);

        return $file->getRealPath();
    }
}
