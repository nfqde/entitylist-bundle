<?php

/*
 * This file is part of the NfqEntityListBundle package.
 *
 * (c) 2017 .NFQ | Netzfrequenz GmbH <info@nfq.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\Bundle\EntityListBundle\Handler;

use Doctrine\ORM\EntityRepository;
use Nfq\Bundle\EntityListBundle\Handler\Result\ListResultConverterInterface;
use Nfq\Bundle\EntityListBundle\Handler\Source\ORMListSource;
use Nfq\Bundle\EntityListBundle\Mapping\Metadata\ListMetadataManager;

/**
 * Class EntityListHandlerFactory.
 */
class EntityListHandlerFactory
{
    /**
     * @var ListMetadataManager
     */
    protected $listMetadataManager;

    /**
     * @var array
     */
    protected $listHandlerConfig;

    /**
     * Constructor.
     *
     * @param ListMetadataManager $listMetadataManager
     * @param array               $listHandlerConfig
     */
    public function __construct(ListMetadataManager $listMetadataManager, array $listHandlerConfig)
    {
        $this->listMetadataManager = $listMetadataManager;
        $this->listHandlerConfig = $listHandlerConfig;
    }

    /**
     * Creates entity list handler.
     *
     * @param EntityRepository                  $entityRepository
     * @param ListResultConverterInterface|null $resultConverter
     *
     * @return EntityListHandlerInterface
     */
    public function getEntityListHandler($entityRepository, ListResultConverterInterface $resultConverter = null)
    {
        $metadata = $this->listMetadataManager->getListMetadata($entityRepository->getClassName());
        $listSource = new ORMListSource($metadata, $this->listHandlerConfig, $entityRepository);

        return new EntityListHandler($listSource, $resultConverter);
    }
}
