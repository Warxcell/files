<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\DependencyInjection;

use Arxy\FilesBundle\Storage;
use League\Flysystem\FilesystemOperator;
use LogicException;
use function get_class;

class StorageFactory
{
    public static function factory(object $service): Storage
    {
        switch (true) {
            case $service instanceof Storage:
                return $service;
            case $service instanceof FilesystemOperator:
                return new Storage\FlysystemStorage($service);
            default:
                throw new LogicException('Class '.get_class($service).' not supported');
        }
    }
}

