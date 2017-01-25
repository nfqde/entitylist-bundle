<?php

/*
 * This file is part of the NfqEntityListBundle package.
 *
 * (c) 2017 .NFQ | Netzfrequenz GmbH <info@nfq.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\Bundle\EntityListBundle\Handler\Result;

/**
 * Interface ListResultConverterInterface.
 */
interface ListResultConverterInterface
{
    /**
     * Converts list items to other objects.
     *
     * @param array $items
     *
     * @return array
     */
    public function convert(array $items);
}
