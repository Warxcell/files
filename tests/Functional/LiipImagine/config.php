<?php

declare(strict_types=1);

use Arxy\FilesBundle\LiipImagine\FileFilterPathResolver;
use Arxy\FilesBundle\NamingStrategy\SplitHashStrategy;
use Arxy\FilesBundle\Tests\Functional\Entity\File;
use Arxy\FilesBundle\Tests\Functional\Repository\FileRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('arxy_files', [
        'managers' => [
            'public' => [
                'driver' => 'orm',
                'class' => File::class,
                'storage' => 'in_memory',
                'naming_strategy' => SplitHashStrategy::class,
            ],
        ],
    ]);

    $services = $container->services();
    $services
        ->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services->set(FileRepository::class);
    $services->set(SplitHashStrategy::class);
    $services->set(FileFilterPathResolver::class);

    $container->extension('liip_imagine', [
        'driver' => 'gd',
        'loaders' => [
            'default' => [
                'flysystem' => [
                    'filesystem_service' => 'in_memory',
                ],
            ],
        ],
        'data_loader' => 'default',
        'resolvers' => [
            'default' => [
                'web_path' => null,
            ],
        ],
        'filter_sets' => [
            'thumbnail' => [
                'quality' => 75,
                'filters' => [
                    'thumbnail' => [
                        'size' => [120, 90],
                        'mode' => 'outbound',
                    ],
                    'background' => [
                        'size' => [124, 94],
                        'position' => 'center',
                        'color' => '#000000',
                    ],
                ],
            ],
        ],
    ]);
};
