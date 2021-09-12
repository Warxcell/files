<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Preview;

use Arxy\FilesBundle\Preview\Dimension;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;

class DimensionTest extends TestCase
{
    public function constructorDataProvider(): iterable
    {
        yield [1, 1];
        yield [5, 5];
    }

    /**
     * @dataProvider constructorDataProvider
     */
    public function testConstructor(int $width, int $height): void
    {
        $this->expectNotToPerformAssertions();
        new Dimension($width, $height);
    }

    public function constructorExceptionsDataProvider(): iterable
    {
        yield [
            0,
            1,
            InvalidArgumentException::class,
            'Length of either side cannot be 0 or negative, current size is 0x1',
        ];
        yield [
            1,
            0,
            InvalidArgumentException::class,
            'Length of either side cannot be 0 or negative, current size is 1x0',
        ];
        yield [
            0,
            0,
            InvalidArgumentException::class,
            'Length of either side cannot be 0 or negative, current size is 0x0',
        ];
        yield [
            -1,
            1,
            InvalidArgumentException::class,
            'Length of either side cannot be 0 or negative, current size is -1x1',
        ];
        yield [
            1,
            -1,
            InvalidArgumentException::class,
            'Length of either side cannot be 0 or negative, current size is 1x-1',
        ];
        yield [
            -1,
            -1,
            InvalidArgumentException::class,
            'Length of either side cannot be 0 or negative, current size is -1x-1',
        ];
        yield [null, 1, TypeError::class];
        yield [1, null, TypeError::class];
    }

    /**
     * @dataProvider constructorExceptionsDataProvider
     */
    public function testConstructorExceptions(
        ?int $width,
        ?int $height,
        string $expectException,
        ?string $expectExceptionMessage = null
    ): void {
        $this->expectException($expectException);
        $this->expectExceptionMessage($expectExceptionMessage);

        new Dimension($width, $height);
    }
}
