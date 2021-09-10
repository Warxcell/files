<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\PersistentPathFile;

class PersistentPathStrategyTest extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\PersistentPathStrategy();
    }

    public function getTestCases(): iterable
    {
        yield new NamingStrategyTestCase(
            new PersistentPathFile(
                'original_filename.jpg',
                125,
                '098f6bcd4621d373cade4e832627b4f6',
                'image/jpeg',
                'directory/sub-directory/123.jpg'
            ),
            'directory/sub-directory/',
            '123.jpg'
        );

        yield new NamingStrategyTestCase(
            new PersistentPathFile(
                'original_filename.jpg',
                125,
                '098f6bcd4621d373cade4e832627b4f6',
                'image/jpeg',
                '123.jpg'
            ),
            null,
            '123.jpg'
        );

        yield new NamingStrategyTestCase(
            new PersistentPathFile(
                'original_filename.jpg',
                125,
                '098f6bcd4621d373cade4e832627b4f6',
                'image/jpeg',
                '123'
            ),
            null,
            '123'
        );
    }
}
