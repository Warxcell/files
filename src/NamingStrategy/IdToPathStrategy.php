<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\IdentifiableFile;
use Arxy\FilesBundle\NamingStrategy;

class IdToPathStrategy implements NamingStrategy
{
    public function getDirectoryName(File $file): ?string
    {
        if (!$file instanceof IdentifiableFile) {
            throw InvalidArgumentException::invalidType($file, IdentifiableFile::class);
        }

        $id = (string)$file->getId();

        return chunk_split($id, 1, DIRECTORY_SEPARATOR);
    }

    public function getFileName(File $file): string
    {
        if (!$file instanceof IdentifiableFile) {
            throw InvalidArgumentException::invalidType($file, IdentifiableFile::class);
        }

        return (string)$file->getId();
    }
}
