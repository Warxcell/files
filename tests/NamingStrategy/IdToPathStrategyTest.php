<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class IdToPathStrategyTest extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\IdToPathStrategy();
    }

    public function getTestCases(): iterable
    {
        yield new NamingStrategyTestCase($this->getFile(), '1/2/3/4/5/', '12345');
    }

    private function getFile(): \Arxy\FilesBundle\Model\File
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file->setId(12345);

        return $file;
    }

    public function testInvalidDirectoryFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Arxy\FilesBundle\Model\IdentifiableFile"'
        );

        $this->getStrategy()->getDirectoryName($this->createMock(\Arxy\FilesBundle\Model\File::class));
    }

    public function testInvalidFileName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Arxy\FilesBundle\Model\IdentifiableFile"'
        );

        $this->getStrategy()->getFileName($this->createMock(\Arxy\FilesBundle\Model\File::class));
    }
}
