<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 */
interface ReaderInterface
{
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
}
