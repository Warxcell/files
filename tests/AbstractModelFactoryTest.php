<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\AbstractModelFactory;
use Arxy\FilesBundle\Model\AbstractFile;
use PHPUnit\Framework\TestCase;
use stdClass;

class AbstractModelFactoryTest extends TestCase
{
    public function testCreate()
    {
        $factory = new AbstractModelFactory(File::class);

        $file = $factory->create('name', 12345, 'md5Hash', 'mimeType');

        $this->assertSame('name', $file->getOriginalFilename());
        $this->assertSame(12345, $file->getFileSize());
        $this->assertSame('md5Hash', $file->getMd5Hash());
        $this->assertSame('mimeType', $file->getMimeType());
    }

    public function testInvalidClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class must be sub-class of '.AbstractFile::class);

        new AbstractModelFactory(stdClass::class);
    }
}
