<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('liip_imagine_filter_runtime', '/media/cache/resolve/{filter}/rc/{hash}/{path}')
        ->controller('%liip_imagine.controller.filter_runtime_action%')
        ->methods(['GET'])
        ->requirements([
            'filter' => '[A-z0-9_-]*',
            'path' => '.+',
        ]);

    $routes
        ->add('liip_imagine_filter', '/media/cache/resolve/{filter}/{path}')
        ->controller('%liip_imagine.controller.filter_action%')
        ->methods(['GET'])
        ->requirements([
            'filter' => '[A-z0-9_-]*',
            'path' => '.+',
        ]);
};
