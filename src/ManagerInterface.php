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
     * @throws UnableToUpload
     */
    public function upload(SplFileInfo $file): File;

    /**
     * Get underlying path of file
     */
    public function getPathname(File $file): string;

    /**
     * Reads the content of file object.
     * @throws FileException
     */
    public function read(File $file): string;

    /**
     * @return resource
     * @throws FileException
     */
    public function readStream(File $file);

    /**
     * @throws FileException
     */
    public function write(MutableFile $file, string $contents): void;

    /**
     * @param resource $resource
     * @throws FileException
     */
    public function writeStream(MutableFile $file, $resource): void;

    /**
     * Move underlying file to it's final location.
     * @throws FileException
     */
    public function moveFile(File $file): void;

    /**
     * Remove underlying file.
     * @throws FileException
     */
    public function remove(File $file): void;

    /**
     * @psalm-return class-string<File>
     */
    public function getClass(): string;

    /**
     * Clears internal FileMap of pending files.
     */
    public function clear(): void;
}
