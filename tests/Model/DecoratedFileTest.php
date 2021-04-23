<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Model;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Tests\VirtualFile;
use PHPUnit\Framework\TestCase;

class DecoratedFileTest extends TestCase
{
    private File $decorator;

    public function setUp(): void
    {
        parent::setUp();

        $file = new \Arxy\FilesBundle\Tests\File('filename', 1234, 'md5Hash', 'mimeType');
        $this->decorator = new VirtualFile($file);
    }

    public function testGetOriginalFilename()
    {
        $this->assertSame('filename', $this->decorator->getOriginalFilename());
    }

    public function testSetOriginalFilename()
    {
        $this->decorator->setOriginalFilename('newName');
        $this->assertSame('newName', $this->decorator->getOriginalFilename());
    }

    public function testGetFilesize()
    {
        $this->assertSame(1234, $this->decorator->getFileSize());
    }

    public function testSetFilesize()
    {
        $this->decorator->setFileSize(4321);
        $this->assertSame(4321, $this->decorator->getFileSize());
    }

    public function testGetMd5Hash()
    {
        $this->assertSame('md5Hash', $this->decorator->getMd5Hash());
    }

    public function testSetMd5Hash()
    {
        $this->decorator->setMd5Hash('hashmd5');
        $this->assertSame('hashmd5', $this->decorator->getMd5Hash());
    }

    public function testGetMimeType()
    {
        $this->assertSame('mimeType', $this->decorator->getMimeType());
    }

    public function testSetMimeType()
    {
        $this->decorator->setMimeType('typeMime');
        $this->assertSame('typeMime', $this->decorator->getMimeType());
    }
}