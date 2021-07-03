<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\AbstractFile;
use Arxy\FilesBundle\Model\File;
use SplFileInfo;
use function is_subclass_of;

/**
 * @template T of AbstractFile
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
        if (!is_subclass_of($class, AbstractFile::class)) {
            throw InvalidArgumentException::invalidClass($class, AbstractFile::class);
        }
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
