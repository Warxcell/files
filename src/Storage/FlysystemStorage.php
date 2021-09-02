<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Storage;

use Arxy\FilesBundle\FileException;
use Arxy\FilesBundle\MetadataStorage;
use Arxy\FilesBundle\MigrateableStorage;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Storage;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;

class FlysystemStorage implements Storage, MigrateableStorage, MetadataStorage
{
    private FilesystemOperator $flysystem;

    public function __construct(FilesystemOperator $flysystem)
    {
        $this->flysystem = $flysystem;
    }

    public function read(File $file, string $pathname): string
    {
        try {
            return $this->flysystem->read($pathname);
        } catch (FilesystemException $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    public function readStream(File $file, string $pathname)
    {
        try {
            return $this->flysystem->readStream($pathname);
        } catch (FilesystemException $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    public function write(File $file, string $pathname, $stream): void
    {
        try {
            $this->flysystem->writeStream($pathname, $stream);
        } catch (FilesystemException $exception) {
            throw FileException::unableToWrite($file, $exception);
        }
    }

    public function remove(File $file, string $pathname): void
    {
        try {
            $this->flysystem->delete($pathname);
        } catch (FilesystemException $exception) {
            throw FileException::unableToMove($file, $exception);
        }
    }

    public function migrate(File $file, string $oldPathname, string $newPathname): bool
    {
        try {
            if (!$this->flysystem->fileExists($oldPathname)) {
                return false;
            }

            $this->flysystem->move($oldPathname, $newPathname);

            return true;
        } catch (FilesystemException $exception) {
            throw new FileException($file, 'Unable to migrate file', $exception);
        }
    }

    public function fileSize(File $file, string $pathname): int
    {
        try {
            return $this->flysystem->fileSize($pathname);
        } catch (FilesystemException $e) {
            throw new FileException($file, 'Unable to calculate filesize', $e);
        }
    }

    public function mimeType(File $file, string $pathname): string
    {
        try {
            return $this->flysystem->mimeType($pathname);
        } catch (FilesystemException $e) {
            throw new FileException($file, 'Unable to determine mimeType', $e);
        }
    }
}
