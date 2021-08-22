<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 * @extends Storage<T>
 */
interface MetadataStorage extends Storage
{
    /**
     * @param T $file
     * @throws FileException
     */
    public function fileSize(File $file, string $pathname): int;

    /**
     * @param T $file
     * @throws FileException
     */
    public function mimeType(File $file, string $pathname): string;

    /**
     * @param T $file
     * @throws FileException
     */
    public function fileExists(File $file, string $pathname): bool;
}