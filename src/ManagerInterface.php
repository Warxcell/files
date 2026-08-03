<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use SplFileInfo;

/**
 * @template T of File
 * @template C
 * @extends UploaderInterface<T, C>
 * @extends ReaderInterface<T>
 */
interface ManagerInterface extends UploaderInterface, ReaderInterface
{
    /**
     * Get underlying path of file
     * @param T $file
     */
    public function getPathname(File $file): string;

    /**
     * @param T & MutableFile $file
     * @param SplFileInfo $splFileInfo
     * @throws FileException
     */
    public function write(MutableFile $file, SplFileInfo $splFileInfo): void;

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
