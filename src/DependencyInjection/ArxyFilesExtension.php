<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\DependencyInjection;

use Arxy\FilesBundle\DelegatingManager;
use Arxy\FilesBundle\EventListener\DoctrineORMListener;
use Arxy\FilesBundle\Form\Type\FileType;
use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\ModelFactory;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Twig\FilesExtension;
use League\Flysystem\FilesystemOperator;
use League\MimeTypeDetection\MimeTypeDetector;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class ArxyFilesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

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

        $totalManagers = count($config['managers']);
        if ($totalManagers === 0) {
            return;
        }

        $autowired = $totalManagers === 1;

        foreach ($config['managers'] as $serviceId => &$managerConfig) {
            $definition = $this->createManagerDefinition(
                $managerConfig['class'],
                $managerConfig['flysystem'] ?? ($autowired ? FilesystemOperator::class : null),
                $managerConfig['naming_strategy'] ?? ($autowired ? NamingStrategy::class : null),
                $managerConfig['repository'] ?? ($autowired ? Repository::class : null),
                $managerConfig['mime_type_detector'] ?? ($autowired ? MimeTypeDetector::class : null),
                $managerConfig['model_factory'] ?? ($autowired ? ModelFactory::class : null)
            );
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
                            static fn(array $config): Reference => $config['reference'],
                            $config['managers']
                        ),
                    ]
                )
            );
            $container->setAlias(ManagerInterface::class, 'arxy_files.delegating_manager');
        }
    }

    private function createListenerDefinition(string $driver, string $serviceId, bool $preRemove = false): Definition
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
                throw new LogicException('Driver not supported '.$driver);
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
        $definition->setArgument('$filesystem', $flysystem ? new Reference($flysystem) : null);
        $definition->setArgument('$namingStrategy', $namingStrategy ? new Reference($namingStrategy) : null);
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
}
