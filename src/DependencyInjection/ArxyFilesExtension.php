<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\DependencyInjection;

use Arxy\FilesBundle\DelegatingManager;
use Arxy\FilesBundle\EventListener\DoctrineORMListener;
use Arxy\FilesBundle\Form\Type\FileType;
use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Storage;
use Arxy\FilesBundle\Twig\FilesExtension;
use Arxy\FilesBundle\Twig\FilesRuntime;
use Doctrine\DBAL\Events as DbalEvents;
use Doctrine\ORM\Events as OrmEvents;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

use function array_key_first;
use function count;

class ArxyFilesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if ($config['form']) {
            $formDefinition = new Definition(FileType::class);
            $formDefinition->setAutowired(true);
            $formDefinition->setAutoconfigured(true);
            $container->setDefinition(FileType::class, $formDefinition);
        }

        if ($config['twig']) {
            $filesExtension = new Definition(FilesExtension::class);
            $filesExtension->setAutowired(true);
            $filesExtension->setAutoconfigured(true);
            $container->setDefinition(FilesExtension::class, $filesExtension);

            $filesExtension = new Definition(FilesRuntime::class);
            $filesExtension->setAutowired(true);
            $filesExtension->setAutoconfigured(true);
            $container->setDefinition(FilesRuntime::class, $filesExtension);
        }

        $totalManagers = count($config['managers']);
        if ($totalManagers === 0) {
            return;
        }

        $references = [];

        foreach ($config['managers'] as $serviceId => $managerConfig) {
            $definition = $this->createManagerDefinition(
                $managerConfig['class'],
                $managerConfig['storage']['service_id'],
                $managerConfig['naming_strategy']['service_id'],
                $managerConfig['repository'],
                $managerConfig['mime_type_detector'],
                $managerConfig['model_factory'],
            );
            $definition->addTag('arxy_files.manager', ['storage' => $serviceId]);
            $container->setDefinition($serviceId, $definition);

            $container->setDefinition(
                'arxy_files.listener.' . $serviceId,
                $this->createListenerDefinition($managerConfig['driver'], $serviceId)
            );

            $container->registerAliasForArgument($serviceId, ManagerInterface::class);
            $references[] = new Reference($serviceId);
        }

        if ($totalManagers > 1) {
            $container->setDefinition(
                'arxy_files.delegating_manager',
                new Definition(
                    DelegatingManager::class,
                    [
                        '$managers' => $references,
                    ]
                )
            );
            $container->setAlias(ManagerInterface::class, 'arxy_files.delegating_manager');
        } else {
            /** @psalm-suppress PossiblyNullArgument */
            $container->setAlias(ManagerInterface::class, array_key_first($config['managers']));
        }
    }

    private function createManagerDefinition(
        string $class,
        string $storage,
        string $namingStrategy,
        ?string $repository,
        ?string $mimeTypeDetector,
        ?string $modelFactory
    ): Definition {
        $storageDefinition = new Definition(Storage::class);
        $storageDefinition->setFactory([StorageFactory::class, 'factory']);
        $storageDefinition->setArgument(0, new Reference($storage));

        $definition = new Definition(Manager::class, ['$class' => $class]);
        $definition->setArgument('$storage', $storageDefinition);
        $definition->setArgument('$namingStrategy', new Reference($namingStrategy));
        $definition->setArgument(
            '$repository',
            $repository ? new Reference($repository, ContainerInterface::NULL_ON_INVALID_REFERENCE) : null
        );
        $definition->setArgument(
            '$mimeTypeDetector',
            $mimeTypeDetector ? new Reference($mimeTypeDetector, ContainerInterface::NULL_ON_INVALID_REFERENCE) : null
        );
        $definition->setArgument(
            '$modelFactory',
            $modelFactory ? new Reference($modelFactory, ContainerInterface::NULL_ON_INVALID_REFERENCE) : null
        );

        $definition->setArgument(
            '$eventDispatcher',
            new Reference(EventDispatcherInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE)
        );

        return $definition;
    }

    private function createListenerDefinition(string $driver, string $serviceId): Definition
    {
        switch ($driver) {
            case 'orm':
                $definition = new Definition(DoctrineORMListener::class);
                $definition->setArgument('$manager', new Reference($serviceId));
                $definition->addTag('doctrine.event_listener', ['event' => OrmEvents::postPersist, 'lazy' => true]);
                $definition->addTag('doctrine.event_listener', ['event' => OrmEvents::postRemove, 'lazy' => true]);
                $definition->addTag('doctrine.event_listener', ['event' => OrmEvents::onClear, 'lazy' => true]);
                $definition->addTag(
                    'doctrine.event_listener',
                    ['event' => DbalEvents::onTransactionCommit, 'lazy' => true]
                );
                $definition->addTag(
                    'doctrine.event_listener',
                    ['event' => DbalEvents::onTransactionRollBack, 'lazy' => true]
                );

                return $definition;
            default:
                throw new LogicException('Driver not supported ' . $driver);
        }
    }
}
