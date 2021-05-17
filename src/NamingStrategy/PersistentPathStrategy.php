<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\PathAwareFile;
use Arxy\FilesBundle\NamingStrategy;

final class PersistentPathStrategy implements NamingStrategy
{
    public function getDirectoryName(File $file): ?string
    {
        if (!$file instanceof PathAwareFile) {
            throw InvalidArgumentException::invalidType($file, PathAwareFile::class);
        }
        $pathname = $file->getPathname();

        $directory = dirname($pathname);
        if ($directory === '.') {
            return null;
        }

        return $directory.DIRECTORY_SEPARATOR;
    }

    public function getFileName(File $file): string
    {
        if (!$file instanceof PathAwareFile) {
            throw InvalidArgumentException::invalidType($file, PathAwareFile::class);
        }
        $pathname = $file->getPathname();

        return basename($pathname);
    }
}
