<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use SplFileInfo;

interface ManagerInterface
{
    /**
     * Converts SplFileInfo instance to file object.
     */
    public function upload(SplFileInfo $file): File;

    /**
     * Get underlying path of file
     */
    public function getPathname(File $file): string;

    /**
     * Reads the content of file object.
     */
    public function read(File $file): string;

    /**
     * @return resource
     */
    public function readStream(File $file);

    /**
     * Refreshes mimeType, size and md5 hash from underlying file.
     */
    public function refresh(MutableFile $file): void;

    /**
     * Rename underlying from $oldStrategy to configured one.
     */
    public function migrate(File $file, NamingStrategy $oldStrategy): bool;

    /**
     * Move underlying file to it's final location.
     */
    public function moveFile(File $file): void;

    /**
     * Remove underlying file.
     */
    public function remove(File $file): void;

    public function getClass(): string;

    /**
     * Clears internal FileMap of pending files.
     */
    public function clear(): void;
}
