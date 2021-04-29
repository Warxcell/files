<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Model;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Tests\VirtualFile;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DecoratedFileTest extends TestCase
{
    private File $decorator;
    private DateTimeImmutable $createdAt;

    public function setUp(): void
    {
        parent::setUp();

        $this->createdAt = new DateTimeImmutable();
        $file = new \Arxy\FilesBundle\Tests\File('filename', 1234, 'md5Hash', 'mimeType');
        $file->setId(98);
        $file->setCreatedAt($this->createdAt);
        $this->decorator = new VirtualFile($file);
    }

    public function testGetId()
    {
        self::assertSame(98, $this->decorator->getId());
    }

    public function testGetOriginalFilename()
    {
        self::assertSame('filename', $this->decorator->getOriginalFilename());
    }

    public function testSetOriginalFilename()
    {
        $this->decorator->setOriginalFilename('newName');
        self::assertSame('newName', $this->decorator->getOriginalFilename());
    }

    public function testGetFilesize()
    {
        self::assertSame(1234, $this->decorator->getFileSize());
    }

    public function testSetFilesize()
    {
        $this->decorator->setFileSize(4321);
        self::assertSame(4321, $this->decorator->getFileSize());
    }

    public function testGetMd5Hash()
    {
        self::assertSame('md5Hash', $this->decorator->getMd5Hash());
    }

    public function testSetMd5Hash()
    {
        $this->decorator->setMd5Hash('hashmd5');
        self::assertSame('hashmd5', $this->decorator->getMd5Hash());
    }

    public function testGetMimeType()
    {
        self::assertSame('mimeType', $this->decorator->getMimeType());
    }

    public function testSetMimeType()
    {
        $this->decorator->setMimeType('typeMime');
        self::assertSame('typeMime', $this->decorator->getMimeType());
    }

    public function testGetCreatedAt()
    {
        self::assertSame($this->createdAt, $this->decorator->getCreatedAt());
    }

    public function testSetCreatedAt()
    {
        $now = new DateTimeImmutable();
        $this->decorator->setCreatedAt($now);
        self::assertSame($now, $this->decorator->getCreatedAt());
    }
}
