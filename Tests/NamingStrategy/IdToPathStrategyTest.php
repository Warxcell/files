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
        $file = new File();
        $file->setId(12345);

        return $file;
    }
}