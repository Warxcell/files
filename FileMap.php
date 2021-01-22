<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * Holds map of files to be uploaded.
 */
class FileMap
{
    /**
     * @var \SplFileInfo[]
     */
    private array $map = [];

    public function put(File $file, \SplFileInfo $fileInfo): void
    {
        $this->map[$this->getObjectId($file)] = $fileInfo;
    }

    public function has(File $file): bool
    {
        return isset($this->map[$this->getObjectId($file)]);
    }

    public function get(File $file): \SplFileInfo
    {
        return $this->map[$this->getObjectId($file)];
    }

    public function remove(File $file): void
    {
        unset($this->map[$this->getObjectId($file)]);
    }

    private function getObjectId(File $file): int
    {
        return spl_object_id($file);
    }
}
