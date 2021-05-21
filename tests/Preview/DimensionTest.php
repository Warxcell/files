<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Preview;

use Arxy\FilesBundle\Preview\Dimension;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TypeError;

class DimensionTest extends TestCase
{
    public function constructorDataProvider()
    {
        return [
            [1, 1],
            [5, 5],
            [
                0,
                1,
                InvalidArgumentException::class,
                'Length of either side cannot be 0 or negative, current size is 0x1',
            ],
            [
                1,
                0,
                InvalidArgumentException::class,
                'Length of either side cannot be 0 or negative, current size is 1x0',
            ],
            [
                0,
                0,
                InvalidArgumentException::class,
                'Length of either side cannot be 0 or negative, current size is 0x0',
            ],
            [
                -1,
                1,
                InvalidArgumentException::class,
                'Length of either side cannot be 0 or negative, current size is -1x1',
            ],
            [
                1,
                -1,
                InvalidArgumentException::class,
                'Length of either side cannot be 0 or negative, current size is 1x-1',
            ],
            [
                -1,
                -1,
                InvalidArgumentException::class,
                'Length of either side cannot be 0 or negative, current size is -1x-1',
            ],
            [null, 1, TypeError::class],
            [1, null, TypeError::class],
        ];
    }

    /**
     * @dataProvider constructorDataProvider
     */
    public function testConstructor(
        ?int $width,
        ?int $height,
        ?string $expectException = null,
        ?string $expectExceptionMessage = null
    ): void {
        if ($expectException !== null) {
            $this->expectException($expectException);
            $this->expectExceptionMessage($expectExceptionMessage);
        } else {
            $this->expectNotToPerformAssertions();
        }

        new Dimension($width, $height);
    }
}
