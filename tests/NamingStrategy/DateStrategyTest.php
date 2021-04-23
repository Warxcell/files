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

    public function getExpectedDirectoryName(): string
    {
        return '2021/03/19/';
    }

    public function getExpectedFileName(): string
    {
        return '098f6bcd4621d373cade4e832627b4f6';
    }

    public function getFile(): \Arxy\FilesBundle\Model\File
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file->setCreatedAt(new \DateTimeImmutable('2021-03-19 22:10:00'));

        return $file;
    }
}
