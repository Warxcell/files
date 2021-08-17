<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface Storage
{
    public function read(File $file, string $pathname): string;

    /**
     * @return resource
     */
    public function readStream(File $file, string $pathname);

    /**
     * @param resource $stream
     */
    public function write(File $file, string $pathname, $stream): void;

    public function remove(File $file, string $pathname): void;
}

