<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Utility\FileUtility;
use OutOfBoundsException;
use SplFileInfo;

use function spl_object_id;
use function sprintf;

/**
 * Holds map of files to be uploaded.
 * @internal
 * @template T of File
 */
final class FileMap
{
    /** @var array<int, SplFileInfo> */
    private array $map = [];
    /** @var array<int, T> */
    private array $pendingFiles = [];

    /**
     * @param array<int, SplFileInfo> $files
     * @param array<int, T> $splFiles
     */
    public function __construct(array $files = [], array $splFiles = [])
    {
        $this->map = $files;
        $this->pendingFiles = $splFiles;
    }

    /**
     * @return T|null
     */
    public function findByHashAndSize(string $hash, int $size): ?File
    {
        foreach ($this->pendingFiles as $file) {
            if ($file->getHash() === $hash && $file->getSize() === $size) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @param T $file
     */
    public function put(File $file, SplFileInfo $fileInfo): void
    {
        $id = $this->getObjectId($file);
        $this->map[$id] = $fileInfo;
        $this->pendingFiles[$id] = $file;
    }

    /**
     * @param T $file
     */
    public function get(File $file): ?SplFileInfo
    {
        return $this->map[$this->getObjectId($file)] ?? null;
    }

    /**
     * @param T $file
     */
    public function remove(File $file): void
    {
        $id = $this->getObjectId($file);
        unset($this->map[$id]);
        unset($this->pendingFiles[$id]);
    }

    /**
     * @param T $file
     */
    private function getObjectId(File $file): int
    {
        return spl_object_id($file);
    }
}
