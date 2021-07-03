<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use SplFileInfo;
use function spl_object_id;

/**
 * Holds map of files to be uploaded.
 * @internal
 * @template T of File
 */
final class FileMap
{
    /**
     * @var SplFileInfo[]
     */
    private array $map = [];
    /** @var array<int, T> */
    private array $pendingFiles = [];

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
    private function getObjectId(File $file): int
    {
        return spl_object_id($file);
    }

    /**
     * @param T $file
     */
    public function get(File $file): SplFileInfo
    {
        if (!$this->has($file)) {
            throw InvalidArgumentException::fileNotExistsInMap($file);
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
