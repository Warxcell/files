<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class DateStrategyTest extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\DateStrategy();
    }

    public function getTestCases(): iterable
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file->setCreatedAt(new \DateTimeImmutable('2021-03-19 22:10:00'));
        yield new NamingStrategyTestCase($file, '2021/03/19/', '098f6bcd4621d373cade4e832627b4f6');
    }
}
