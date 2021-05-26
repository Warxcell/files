<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\AbstractFile;
use Arxy\FilesBundle\Model\File;
use SplFileInfo;
use function is_subclass_of;

class AbstractModelFactory implements ModelFactory
{
    private string $class;

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
        int $fileSize,
        string $hash,
        string $mimeType
    ): File {
        return new $this->class($originalFilename, $fileSize, $hash, $mimeType);
    }
}
