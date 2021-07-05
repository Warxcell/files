<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @psalm-suppress all
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('arxy_files');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->fixXmlConfig('manager')
            ->children()
            ->booleanNode('form')->defaultTrue()->end()
            ->booleanNode('twig')->defaultTrue()->end()
            ->arrayNode('managers')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->performNoDeepMerging()
            ->children()
            ->enumNode('driver')->values(['orm'])->isRequired()->end()
            ->scalarNode('class')->isRequired()->end()
            ->scalarNode('flysystem')->defaultNull()->end()
            ->scalarNode('naming_strategy')->defaultNull()->end()
            ->scalarNode('repository')->defaultNull()->end()
            ->scalarNode('mime_type_detector')->defaultNull()->end()
            ->scalarNode('model_factory')->defaultNull()->end()
            ->end()
            ->end()
            ->defaultValue([])
            ->end()
            ->end();

        return $treeBuilder;
    }
}
