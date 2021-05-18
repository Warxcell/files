<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;
use PHPUnit\Framework\TestCase;

class UuidV4StrategyTest extends TestCase
{
    private function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\UuidV4Strategy();
    }

    private function getFile(): File
    {
        return new File(
            'original_filename.jpg',
            125,
            '098f6bcd4621d373cade4e832627b4f6',
            'image/jpeg'
        );
    }

    public function testDirectoryName()
    {
        self::assertNull($this->getStrategy()->getDirectoryName($this->getFile()));
    }

    public function testGetFilename()
    {
        $strategy = $this->getStrategy();

        $filename1 = $strategy->getFileName($this->getFile());
        self::assertTrue(uuid_is_valid($filename1));

        $filename2 = $strategy->getFileName($this->getFile());
        self::assertTrue(uuid_is_valid($filename2));

        self::assertNotSame($filename1, $filename2);
    }
}
