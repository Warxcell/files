<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class SplitHashStrategyTest extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\SplitHashStrategy();
    }

    public function getTestCases(): iterable
    {
        yield new NamingStrategyTestCase(
            new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg'),
            '098f6bcd/4621d373/cade4e83/2627b4f6/', '098f6bcd4621d373cade4e832627b4f6'
        );
    }

    public function testIncorrectSplitLength()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$splitLength parameter must be modulus of 32');

        new NamingStrategy\SplitHashStrategy(6);
    }
}
