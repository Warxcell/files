<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class SplitHashStrategy extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\SplitHashStrategy();
    }

    public function getExpectedDirectoryName(): string
    {
        return '098f/6bcd/4621/d373/cade/4e83/2627/b4f6/';
    }

    public function getExpectedFileName(): string
    {
        return '098f6bcd4621d373cade4e832627b4f6';
    }

    public function getFile(): \Arxy\FilesBundle\Model\File
    {
        $file = new File();
        $file->setMd5Hash('098f6bcd4621d373cade4e832627b4f6');

        return $file;
    }
}