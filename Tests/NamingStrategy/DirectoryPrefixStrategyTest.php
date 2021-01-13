<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class DirectoryPrefixStrategyTest extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\DirectoryPrefixStrategy(
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return '1/2/3/';
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return '123';
                }
            },
            'cache/'
        );
    }

    public function getExpectedDirectoryName(): string
    {
        return 'cache/1/2/3/';
    }

    public function getExpectedFileName(): string
    {
        return '123';
    }

    public function getFile(): \Arxy\FilesBundle\Model\File
    {
        $file = new File();
        $file->setId(12345);
        $file->setOriginalFilename('original_filename.jpg');

        return $file;
    }
}