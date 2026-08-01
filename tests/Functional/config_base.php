<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'test' => true,
        'secret' => 'ydslvbxkcen473w89r8qkaponbcyd',
        'router' => [
            'resource' => '%kernel.project_dir%/routes.php',
            'utf8' => true,
        ],
    ]);

    $container->extension('doctrine', [
        'dbal' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'charset' => 'UTF8',
        ],
        'orm' => [
            'enable_native_lazy_objects' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
            'auto_mapping' => true,
            'mappings' => [
                'ArxyFilesBundleTestsFunctionalEntity' => [
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/Entity',
                    'is_bundle' => false,
                    'prefix' => 'Arxy\FilesBundle\Tests\Functional\Entity',
                ],
            ],
        ],
    ]);

    $container->extension('flysystem', [
        'storages' => [
            'in_memory' => [
                'adapter' => 'memory',
            ],
        ],
    ]);
};
