<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\NamingStrategy;

class PersistentPathStrategyTest extends AbstractStrategyTest
{
    protected static function getConfig(): string
    {
        return __DIR__.'/PersistPathStrategy/config.yml';
    }

    protected static function getBundles(): array
    {
        return [];
    }
}
