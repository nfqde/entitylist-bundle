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
 * Class NoConvertResultConverter.
 */
class NoConvertResultConverter implements ListResultConverterInterface
{
    /**
     * Does not convert list items.
     *
     * @param array $items
     *
     * @return array
     */
    public function convert(array $items)
    {
        return $items;
    }
}
