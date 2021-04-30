<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\AbstractModelFactory;
use PHPUnit\Framework\TestCase;
use stdClass;

class AbstractModelFactoryTest extends TestCase
{
    public function testCreate()
    {
        $factory = new AbstractModelFactory(File::class);
        $file = $factory->create(new \SplFileInfo(__DIR__.'/files/image1.jpg'), 'name', 12345, 'md5Hash', 'mimeType');

        self::assertSame('name', $file->getOriginalFilename());
        self::assertSame(12345, $file->getFileSize());
        self::assertSame('md5Hash', $file->getMd5Hash());
        self::assertSame('mimeType', $file->getMimeType());
    }

    public function testInvalidClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected class of type "Arxy\FilesBundle\Model\AbstractFile", "stdClass" given'
        );

        new AbstractModelFactory(stdClass::class);
    }
}
