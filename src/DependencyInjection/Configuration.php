<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('arxy_files');
        $rootNode = $treeBuilder->getRootNode();
        assert($rootNode instanceof ArrayNodeDefinition);

        // @formatter:off
        /**
         * @psalm-suppress PossiblyNullReference
         * @psalm-suppress PossiblyUndefinedMethod
         */
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
                            ->arrayNode('storage')
                                ->isRequired()
                                ->beforeNormalization()
                                    ->ifString()
                                        ->then(static fn (string $value): array => ['service_id' => $value])
                                    ->end()
                                    ->children()
                                        ->scalarNode('service_id')->isRequired()->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('naming_strategy')
                                ->isRequired()
                                ->beforeNormalization()
                                    ->ifString()
                                        ->then(static fn (string $value): array => ['service_id' => $value])
                                    ->end()
                                    ->children()
                                        ->scalarNode('service_id')->isRequired()->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('repository')->defaultNull()->end()
                            ->scalarNode('mime_type_detector')->defaultNull()->end()
                            ->scalarNode('model_factory')->defaultNull()->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
            ->end();
        // @formatter:on
        return $treeBuilder;
    }
}
