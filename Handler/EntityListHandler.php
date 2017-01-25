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

use Nfq\Bundle\EntityListBundle\Handler\Result\ListResultConverterInterface;
use Nfq\Bundle\EntityListBundle\Handler\Result\NoConvertResultConverter;
use Nfq\Bundle\EntityListBundle\Handler\Source\ListSourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityListHandler.
 */
class EntityListHandler implements EntityListHandlerInterface
{
    /**
     * @var ListSourceInterface
     */
    protected $listSource;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ListResultConverterInterface
     */
    protected $resultConverter;

    /**
     * Constructor.
     *
     * @param ListSourceInterface          $listSource
     * @param ListResultConverterInterface $resultConverter
     */
    public function __construct(
        ListSourceInterface $listSource,
        ListResultConverterInterface $resultConverter = null
    ) {
        $this->listSource = $listSource;
        $this->resultConverter = $resultConverter ? : new NoConvertResultConverter();
    }

    /**
     * Returns filtered results by request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function getResults(Request $request)
    {
        return $this->resultConverter->convert(
            $this->listSource->processSource($request)
        );
    }

    /**
     * Returns found list items count.
     *
     * @param Request $request
     *
     * @return int
     */
    public function getItemsCount(Request $request)
    {
        return $this->listSource->getItemsCount($request);
    }

    /**
     * Returns array of sortable fields.
     *
     * @return array
     */
    public function getSortableFields()
    {
        return $this->listSource->getSortableFields();
    }

    /**
     * Returns array of filter fields with possible operators.
     *
     * @return array
     */
    public function getFilterableFields()
    {
        return $this->listSource->getFilterableFields();
    }
}
