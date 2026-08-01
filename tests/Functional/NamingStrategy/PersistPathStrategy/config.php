<?php

declare(strict_types=1);

use Arxy\FilesBundle\EventListener\PathAwareListener;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\NamingStrategy\PersistentPathStrategy;
use Arxy\FilesBundle\NamingStrategy\SplitHashStrategy;
use Arxy\FilesBundle\Tests\Functional\Entity\EmbeddableFilePersistentPath;
use Arxy\FilesBundle\Tests\Functional\Entity\PersistentPathFile;
use Arxy\FilesBundle\Tests\Functional\Repository\FileRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->extension('arxy_files', [
        'managers' => [
            'public' => [
                'driver' => 'orm',
                'class' => PersistentPathFile::class,
                'storage' => 'in_memory',
                'naming_strategy' => PersistentPathStrategy::class,
            ],
            'embeddable' => [
                'driver' => 'orm',
                'class' => EmbeddableFilePersistentPath::class,
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

    $services->set(SplitHashStrategy::class);

    $services
        ->set(PathAwareListener::class)
        ->arg('$namingStrategy', service(SplitHashStrategy::class));
};
