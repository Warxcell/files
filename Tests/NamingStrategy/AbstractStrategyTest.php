<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use PHPUnit\Framework\TestCase;

abstract class AbstractStrategyTest extends TestCase
{
    abstract public function getStrategy(): NamingStrategy;

    abstract public function getExpectedDirectoryName(): string;

    abstract public function getExpectedFileName(): string;

    abstract public function getFile(): File;

    public function testDirectoryName()
    {
        $this->assertEquals(
            $this->getExpectedDirectoryName(),
            $this->getStrategy()->getDirectoryName($this->getFile())
        );
    }

    public function testFilename()
    {
        $this->assertEquals(
            $this->getExpectedFileName(),
            $this->getStrategy()->getFileName($this->getFile())
        );
    }
}