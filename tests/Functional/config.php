<?php

declare(strict_types=1);

use Arxy\FilesBundle\Entity\EmbeddableFile;
use Arxy\FilesBundle\NamingStrategy\NullDirectoryStrategy;
use Arxy\FilesBundle\NamingStrategy\SplitHashStrategy;
use Arxy\FilesBundle\Tests\Functional\Entity\File;
use Arxy\FilesBundle\Tests\Functional\Repository\FileRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->extension('arxy_files', [
        'managers' => [
            'public' => [
                'driver' => 'orm',
                'class' => File::class,
                'storage' => 'in_memory',
                'naming_strategy' => SplitHashStrategy::class,
                'repository' => FileRepository::class,
            ],
            'embeddable_manager' => [
                'driver' => 'orm',
                'class' => EmbeddableFile::class,
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

    $services
        ->set(NullDirectoryStrategy::class)
        ->decorate(SplitHashStrategy::class)
        ->args([service('.inner')]);
};
