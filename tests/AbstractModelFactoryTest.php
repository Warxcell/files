<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\AbstractModelFactory;
use PHPUnit\Framework\TestCase;
use stdClass;

class AbstractModelFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new AbstractModelFactory(File::class);
        $file = $factory->create(new \SplFileInfo(__DIR__.'/files/image1.jpg'), 'name', 12345, 'hash', 'mimeType');

        self::assertSame('name', $file->getOriginalFilename());
        self::assertSame(12345, $file->getSize());
        self::assertSame('hash', $file->getHash());
        self::assertSame('mimeType', $file->getMimeType());
    }

    public function testInvalidClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected class of type "Arxy\FilesBundle\Model\AbstractFile", "stdClass" given'
        );

        new AbstractModelFactory(stdClass::class);
    }
}
