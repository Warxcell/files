<?php

declare(strict_types=1);

use Arxy\FilesBundle\EventListener\PathAwareListener;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\NamingStrategy\PersistentPathStrategy;
use Arxy\FilesBundle\NamingStrategy\UuidV4Strategy;
use Arxy\FilesBundle\Preview\Dimension;
use Arxy\FilesBundle\Preview\DimensionInterface;
use Arxy\FilesBundle\Preview\ImagePreviewGenerator;
use Arxy\FilesBundle\Preview\PreviewGenerator;
use Arxy\FilesBundle\Tests\Functional\Entity\FileWithPreview;
use Arxy\FilesBundle\Tests\Functional\Entity\Preview;
use Arxy\FilesBundle\Tests\Functional\Repository\FileRepository;
use Imagine\Gd\Imagine;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->extension('arxy_files', [
        'managers' => [
            'public' => [
                'driver' => 'orm',
                'class' => FileWithPreview::class,
                'storage' => 'in_memory',
                'naming_strategy' => PersistentPathStrategy::class,
            ],
            'preview' => [
                'driver' => 'orm',
                'class' => Preview::class,
                'storage' => 'in_memory',
                'naming_strategy' => PersistentPathStrategy::class,
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
    $services->set(PersistentPathStrategy::class);
    $services->alias(NamingStrategy::class, PersistentPathStrategy::class);

    $services->set(UuidV4Strategy::class);
    $services
        ->set(PathAwareListener::class)
        ->arg('$namingStrategy', service(UuidV4Strategy::class));

    $services->set(Imagine::class);

    $services
        ->set(ImagePreviewGenerator::class)
        ->arg('$manager', service('public'))
        ->arg('$imagine', service(Imagine::class));

    $services
        ->set(Dimension::class)
        ->arg('$width', 250)
        ->arg('$height', 250);

    $services->alias(DimensionInterface::class, Dimension::class);

    $services
        ->set(PreviewGenerator::class)
        ->arg('$manager', service('preview'))
        ->arg('$generators', [service(ImagePreviewGenerator::class)]);
};
