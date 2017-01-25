<?php

/*
 * This file is part of the NfqEntityListBundle package.
 *
 * (c) 2017 .NFQ | Netzfrequenz GmbH <info@nfq.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\Bundle\EntityListBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 *
 * This is the class that validates and merges configuration from your app/config files.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('nfq_entity_list');
        $rootNode
            ->children()
                ->arrayNode('handler_config')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('bundles_directory')->end()
                        ->scalarNode('page_nr_param_name')->defaultValue('page')->end()
                        ->scalarNode('page_limit_param_name')->defaultValue('page_limit')->end()
                        ->scalarNode('sort_param_name')->defaultValue('order_by')->end()
                        ->scalarNode('filters_param_name')->defaultValue('filters')->end()
                        ->scalarNode('search_param_name')->defaultValue('search')->end()
                        ->integerNode('default_page_limit')->defaultValue(10)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
