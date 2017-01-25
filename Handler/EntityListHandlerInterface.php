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

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface EntityListHandlerInterface.
 */
interface EntityListHandlerInterface
{
    /**
     * Returns filtered results by request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function getResults(Request $request);

    /**
     * Returns found list items count.
     *
     * @param Request $request
     *
     * @return int
     */
    public function getItemsCount(Request $request);

    /**
     * Returns array of sortable fields.
     *
     * @return array
     */
    public function getSortableFields();

    /**
     * Returns array of filter fields.
     *
     * @return array
     */
    public function getFilterableFields();
}
