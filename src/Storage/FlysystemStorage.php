<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Storage;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Storage;
use League\Flysystem\FilesystemOperator;

class FlysystemStorage implements Storage
{
    private FilesystemOperator $flysystem;

    public function __construct(FilesystemOperator $flysystem)
    {
        $this->flysystem = $flysystem;
    }

    public function read(File $file, string $pathname): string
    {
        return $this->flysystem->read($pathname);
    }

    public function readStream(File $file, string $pathname)
    {
        return $this->flysystem->readStream($pathname);
    }

    public function write(File $file, string $pathname, $stream): void
    {
        $this->flysystem->writeStream($pathname, $stream);
    }

    public function remove(File $file, string $pathname): void
    {
        $this->flysystem->delete($pathname);
    }
}

