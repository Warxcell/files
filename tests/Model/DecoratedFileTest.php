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

        $file = new \Arxy\FilesBundle\Tests\File('filename', 1234, 'hash', 'mimeType');
        $file->setId(98);
        $this->createdAt = $file->getCreatedAt();
        $this->decorator = new VirtualFile($file);
    }

    public function testGetOriginalFilename(): void
    {
        self::assertSame('filename', $this->decorator->getOriginalFilename());
    }

    public function testGetSize(): void
    {
        self::assertSame(1234, $this->decorator->getSize());
    }

    public function testGetHash(): void
    {
        self::assertSame('hash', $this->decorator->getHash());
    }

    public function testGetMimeType(): void
    {
        self::assertSame('mimeType', $this->decorator->getMimeType());
    }

    public function testGetCreatedAt(): void
    {
        self::assertSame($this->createdAt, $this->decorator->getCreatedAt());
    }
}
