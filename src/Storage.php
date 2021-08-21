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
     * @throws FileException
     */
    public function read(File $file, string $pathname): string;

    /**
     * @param T $file
     * @return resource
     * @throws FileException
     */
    public function readStream(File $file, string $pathname);

    /**
     * @param T $file
     * @param resource $stream
     * @throws FileException
     */
    public function write(File $file, string $pathname, $stream): void;

    /**
     * @param T $file
     * @param string $pathname
     * @throws FileException
     */
    public function remove(File $file, string $pathname): void;
}

