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

    public function write(MutableFile $file, string $contents): void;

    /**
     * @param resource $resource
     */
    public function writeStream(MutableFile $file, $resource): void;

    /**
     * Move underlying file to it's final location.
     */
    public function moveFile(File $file): void;

    /**
     * Remove underlying file.
     */
    public function remove(File $file): void;

    /**
     * @psalm-param class-string<File> $class
     */
    public function getClass(): string;

    /**
     * Clears internal FileMap of pending files.
     */
    public function clear(): void;
}
