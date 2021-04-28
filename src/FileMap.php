<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use SplFileInfo;

/**
 * Holds map of files to be uploaded.
 * @internal
 */
class FileMap
{
    /**
     * @var SplFileInfo[]
     */
    private array $map = [];
    /** @var File[] */
    private array $pendingFiles = [];

    public function findByHashAndSize(string $md5hash, int $size): ?File
    {
        foreach ($this->pendingFiles as $file) {
            if ($file->getMd5Hash() === $md5hash && $file->getFileSize() === $size) {
                return $file;
            }
        }

        return null;
    }

    public function put(File $file, SplFileInfo $fileInfo): void
    {
        $id = $this->getObjectId($file);
        $this->map[$id] = $fileInfo;
        $this->pendingFiles[$id] = $file;
    }

    public function has(File $file): bool
    {
        return isset($this->map[$this->getObjectId($file)]);
    }

    public function get(File $file): SplFileInfo
    {
        if (!$this->has($file)) {
            throw InvalidArgumentException::fileNotExistsInMap($file);
        }

        return $this->map[$this->getObjectId($file)];
    }

    public function remove(File $file): void
    {
        $id = $this->getObjectId($file);
        unset($this->map[$id]);
        unset($this->pendingFiles[$id]);
    }

    private function getObjectId(File $file): int
    {
        return spl_object_id($file);
    }
}
