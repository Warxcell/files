<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use SplFileInfo;

/**
 * @template T of File
 * @implements ModelFactory<T>
 */
class AbstractModelFactory implements ModelFactory
{
    /**
     * @var class-string<T>
     */
    private string $class;

    /**
     * @param class-string<T> $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function create(
        SplFileInfo $file,
        string $originalFilename,
        int $size,
        string $hash,
        string $mimeType
    ): File {
        return new $this->class($originalFilename, $size, $hash, $mimeType);
    }
}
