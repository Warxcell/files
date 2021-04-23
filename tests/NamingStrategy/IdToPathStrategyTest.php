<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class IdToPathStrategyTest extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\IdToPathStrategy();
    }

    public function getExpectedDirectoryName(): string
    {
        return '1/2/3/4/5/';
    }

    public function getExpectedFileName(): string
    {
        return '12345';
    }

    public function getFile(): \Arxy\FilesBundle\Model\File
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file->setId(12345);

        return $file;
    }
}
