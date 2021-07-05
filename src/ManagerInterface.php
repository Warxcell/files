<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use SplFileInfo;

/**
 * @template T of \Arxy\FilesBundle\Model\File
 */
interface ManagerInterface
{
    /**
     * Converts SplFileInfo instance to file object.
     * @return T
     * @throws UnableToUpload
     */
    public function upload(SplFileInfo $file): File;

    /**
     * Get underlying path of file
     * @param T $file
     */
    public function getPathname(File $file): string;

    /**
     * Reads the content of file object.
     * @param T $file
     * @throws FileException
     */
    public function read(File $file): string;

    /**
     * @param T $file
     * @return resource
     * @throws FileException
     */
    public function readStream(File $file);

    /**
     * @template W of T&MutableFile
     * @param W $file
     * @throws FileException
     */
    public function write(MutableFile $file, string $contents): void;

    /**
     * @template W of T&MutableFile
     * @param W $file
     * @param resource $resource
     * @throws FileException
     */
    public function writeStream(MutableFile $file, $resource): void;

    /**
     * Move underlying file to it's final location.
     * @param T $file
     * @throws FileException
     */
    public function moveFile(File $file): void;

    /**
     * Remove underlying file.
     * @param T $file
     * @throws FileException
     */
    public function remove(File $file): void;

    /**
     * @return class-string<T>
     */
    public function getClass(): string;

    /**
     * Clears internal FileMap of pending files.
     */
    public function clear(): void;
}
