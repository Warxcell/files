<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;
use Symfony\Component\Uid\UuidV4;

class UuidV5StrategyTest extends AbstractStrategyTest
{
    private const UUID_NAMESPACE = '537ed59b-a728-4ff1-93b1-de16e61300ca';

    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\UuidV5Strategy(UuidV4::fromString(self::UUID_NAMESPACE));
    }

    public function getTestCases(): iterable
    {
        yield new NamingStrategyTestCase(
            new File(
                'original_filename.jpg',
                125,
                '098f6bcd4621d373cade4e832627b4f6',
                'image/jpeg'
            ),
            null,
            'c297b526-7801-566f-95fd-7fc409eb4670'
        );

        yield new NamingStrategyTestCase(
            new File(
                'original_filename.jpg',
                125,
                '198f6bcd4621d373cade4e832627b4f6',
                'image/jpeg'
            ),
            null,
            '1d0cb4e1-7cee-5646-8519-e8ccdb773284'
        );
    }
}
