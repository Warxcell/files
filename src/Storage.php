<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 */
interface Storage
{
    /**
     * @param T $file
     */
    public function read(File $file, string $pathname): string;

    /**
     * @param T $file
     * @return resource
     */
    public function readStream(File $file, string $pathname);

    /**
     * @param T $file
     * @param resource $stream
     */
    public function write(File $file, string $pathname, $stream): void;

    /**
     * @param T $file
     * @param string $pathname
     */
    public function remove(File $file, string $pathname): void;
}

