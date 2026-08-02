<?php

declare(strict_types=1);

use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\Functional\Twig\PathResolver as TestPathResolver;
use Arxy\FilesBundle\Twig\PathResolverExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->import('../config.php');

    $services = $container->services();
    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(TestPathResolver::class);
    $services->alias(PathResolver::class, TestPathResolver::class);

    $services->set(PathResolverExtension::class);
};
