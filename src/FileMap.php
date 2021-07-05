<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use OutOfBoundsException;
use SplFileInfo;
use function method_exists;
use function spl_object_id;
use function sprintf;

/**
 * Holds map of files to be uploaded.
 * @internal
 * @template T of File
 * @template S of SplFileInfo
 */
final class FileMap
{
    /** @var array<int, S> */
    private array $map = [];
    /** @var array<int, T> */
    private array $pendingFiles = [];

    /**
     * @param array<int, S> $files
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
     * @param S $fileInfo
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
    private function getObjectId(File $file): int
    {
        return spl_object_id($file);
    }

    /**
     * @param T $file
     * @return S
     */
    public function get(File $file): SplFileInfo
    {
        if (!$this->has($file)) {
            throw new OutOfBoundsException(
                sprintf(
                    'File %s not found in map',
                    method_exists($file, '__toString') ? (string)$file : (string)spl_object_id($file)
                )
            );
        }

        return $this->map[$this->getObjectId($file)];
    }

    /**
     * @param T $file
     */
    public function has(File $file): bool
    {
        return isset($this->map[$this->getObjectId($file)]);
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
}
