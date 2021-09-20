<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\DependencyInjection;

use Arxy\FilesBundle\DependencyInjection\StorageFactory;
use Arxy\FilesBundle\Storage;
use League\Flysystem\FilesystemOperator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

class StorageFactoryTest extends TestCase
{
    public function factoryProvider(): iterable
    {
        yield 'storage' => [$this->createMock(Storage::class), Storage::class];
        yield 'flysystem' => [$this->createMock(FilesystemOperator::class), Storage\FlysystemStorage::class];
    }

    /**
     * @dataProvider factoryProvider
     * @param class-string $expected
     */
    public function testFactory(object $object, string $expected): void
    {
        self::assertInstanceOf($expected, StorageFactory::factory($object));
    }

    public function factoryExceptionProvider(): iterable
    {
        yield 'non-supported class' => [new stdClass(), LogicException::class, 'Class stdClass not supported'];
    }

    /**
     * @dataProvider factoryExceptionProvider
     * @param class-string<Throwable> $expected
     */
    public function testFactoryException(object $object, string $expected, string $expectedMessage): void
    {
        self::expectException($expected);
        self::expectExceptionMessage($expectedMessage);
        StorageFactory::factory($object);
    }
}

