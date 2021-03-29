<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Validator\Constraint;

use Arxy\FilesBundle\Validator\Constraint\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class FileTest extends TestCase
{
    public function testNormalizeBytes()
    {
        $file = new File(['maxSize' => 1000]);

        $this->assertSame(1000, $file->maxSize);
    }

    public function testNormalizeKb()
    {
        $file = new File(['maxSize' => '1k']);

        $this->assertSame(1000, $file->maxSize);
    }

    public function testNormalizeMb()
    {
        $file = new File(['maxSize' => '1M']);

        $this->assertSame(1000000, $file->maxSize);
    }

    public function testNormalizeKi()
    {
        $file = new File(['maxSize' => '1Ki']);

        $this->assertSame(1024, $file->maxSize);
    }

    public function testNormalizeMi()
    {
        $file = new File(['maxSize' => '1Mi']);

        $this->assertSame(1048576, $file->maxSize);
    }

    public function testInvalid()
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('"1 gigabyte" is not a valid maximum size.');
        $this->expectExceptionCode(0);

        new File(['maxSize' => '1 gigabyte']);
    }

    public function testSingleMimeType()
    {
        $file = new File(['mimeTypes' => 'image/jpg']);

        $this->assertCount(1, $file->mimeTypes);
        $this->assertSame('image/jpg', $file->mimeTypes[0]);
    }
}
