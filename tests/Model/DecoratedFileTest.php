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

        $file = new \Arxy\FilesBundle\Tests\File('filename', 1234, 'md5Hash', 'mimeType');
        $file->setId(98);
        $this->createdAt = $file->getCreatedAt();
        $this->decorator = new VirtualFile($file);
    }

    public function testGetOriginalFilename()
    {
        self::assertSame('filename', $this->decorator->getOriginalFilename());
    }

    public function testGetFilesize()
    {
        self::assertSame(1234, $this->decorator->getFileSize());
    }

    public function testGetMd5Hash()
    {
        self::assertSame('md5Hash', $this->decorator->getMd5Hash());
    }

    public function testGetMimeType()
    {
        self::assertSame('mimeType', $this->decorator->getMimeType());
    }

    public function testGetCreatedAt()
    {
        self::assertSame($this->createdAt, $this->decorator->getCreatedAt());
    }
}
