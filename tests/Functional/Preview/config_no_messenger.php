<?php

declare(strict_types=1);

use Arxy\FilesBundle\Preview\PreviewGeneratorListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->import('config.php');

    $services = $container->services();
    $services
        ->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services->set(PreviewGeneratorListener::class);
};
