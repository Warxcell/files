<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\Model\File;

class NamingStrategyTestCase
{
    private File $file;
    private ?string $expectedDirectoryName;
    private string $expectedFilename;

    public function __construct(File $file, ?string $expectedDirectoryName, string $expectedFilename)
    {
        $this->file = $file;
        $this->expectedDirectoryName = $expectedDirectoryName;
        $this->expectedFilename = $expectedFilename;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getExpectedDirectoryName(): ?string
    {
        return $this->expectedDirectoryName;
    }

    public function getExpectedFilename(): string
    {
        return $this->expectedFilename;
    }
}
