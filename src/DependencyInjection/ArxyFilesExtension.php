<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\DependencyInjection;

use Arxy\FilesBundle\DelegatingManager;
use Arxy\FilesBundle\EventListener\DoctrineORMListener;
use Arxy\FilesBundle\Form\Type\FileType;
use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Twig\FilesExtension;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class ArxyFilesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $totalManagers = count($config['managers']);

        if ($config['form']) {
            $formDefinition = new Definition(FileType::class);
            $formDefinition->setAutowired(true);
            $container->setDefinition(FileType::class, $formDefinition);
        }

        if ($config['twig']) {
            $filesExtension = new Definition(FilesExtension::class);
            $filesExtension->setAutowired(true);
            $container->setDefinition(FilesExtension::class, $filesExtension);
        }

        if ($totalManagers === 0) {
            return;
        }

        $autowired = $totalManagers === 1;

        foreach ($config['managers'] as $serviceId => &$managerConfig) {
            $definition = $this->createManagerDefinition(
                $managerConfig['class'],
                $managerConfig['flysystem'],
                $managerConfig['naming_strategy'],
                $managerConfig['repository'],
                $managerConfig['mime_type_detector'],
                $managerConfig['model_factory']
            );
            $definition->setAutowired($autowired);
            $container->setDefinition($serviceId, $definition);

            $container->setDefinition(
                'arxy_files.listener.'.$serviceId,
                $this->createListenerDefinition($managerConfig['driver'], $serviceId)
            );

            $container->registerAliasForArgument($serviceId, ManagerInterface::class);

            $managerConfig['reference'] = new Reference($serviceId);
        }

        if ($autowired) {
            $container->setAlias(ManagerInterface::class, array_key_first($config['managers']));
        } else {
            $container->setDefinition(
                'arxy_files.delegating_manager',
                new Definition(
                    DelegatingManager::class,
                    [
                        '$managers' => array_map(
                            fn($config) => $config['reference'],
                            $config['managers']
                        ),
                    ]
                )
            );
            $container->setAlias(ManagerInterface::class, 'arxy_files.delegating_manager');
        }
    }

    private function createListenerDefinition(string $driver, string $serviceId): Definition
    {
        switch ($driver) {
            case 'orm':
                $definition = new Definition(DoctrineORMListener::class);
                $definition->setArgument('$manager', new Reference($serviceId));
                $definition->addTag('doctrine.event_listener', ['event' => 'postPersist', 'lazy' => true]);
                $definition->addTag('doctrine.event_listener', ['event' => 'preRemove', 'lazy' => true]);
                $definition->addTag('doctrine.event_listener', ['event' => 'onClear', 'lazy' => true]);

                return $definition;
            default:
                throw new \LogicException('Driver not supported '.$driver);
        }
    }

    private function createManagerDefinition(
        string $class,
        ?string $flysystem,
        ?string $namingStrategy,
        ?string $repository,
        ?string $mimeTypeDetector,
        ?string $modelFactory
    ): Definition {
        $definition = new Definition(Manager::class, ['$class' => $class]);
        if ($flysystem !== null) {
            $definition->setArgument('$filesystem', new Reference($flysystem));
        } else {
            $definition->setArgument('$filesystem', null);
        }

        if ($namingStrategy !== null) {
            $definition->setArgument('$namingStrategy', new Reference($namingStrategy));
        } else {
            $definition->setArgument('$repository', null);
        }

        if ($repository !== null) {
            $definition->setArgument('$repository', new Reference($repository));
        } else {
            $definition->setArgument('$repository', null);
        }

        if ($mimeTypeDetector !== null) {
            $definition->setArgument('$mimeTypeDetector', new Reference($mimeTypeDetector));
        } else {
            $definition->setArgument('$mimeTypeDetector', null);
        }

        if ($modelFactory !== null) {
            $definition->setArgument('$modelFactory', new Reference($modelFactory));
        } else {
            $definition->setArgument('$modelFactory', null);
        }

        $definition->setArgument(
            '$eventDispatcher',
            new Reference(EventDispatcherInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE)
        );

        return $definition;
    }
}
