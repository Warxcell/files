<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class AppendExtensionStrategy extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\AppendExtensionStrategy(
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return '1/2/3/';
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return '123';
                }
            }
        );
    }

    public function getExpectedDirectoryName(): string
    {
        return '1/2/3/';
    }

    public function getExpectedFileName(): string
    {
        return '123.jpg';
    }

    public function getFile(): \Arxy\FilesBundle\Model\File
    {
        $file = new File();
        $file->setId(12345);
        $file->setOriginalFilename('original_filename.jpg');

        return $file;
    }
}