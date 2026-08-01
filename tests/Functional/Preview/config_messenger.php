<?php

declare(strict_types=1);

use Arxy\FilesBundle\Preview\GeneratePreviewMessageHandler;
use Arxy\FilesBundle\Preview\PreviewGeneratorMessengerListener;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->import('config.php');

    $container->extension('framework', [
        'messenger' => [],
    ]);

    $services = $container->services();
    $services
        ->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services->set(GeneratePreviewMessageHandler::class);
    $services->set(PreviewGeneratorMessengerListener::class);
};
