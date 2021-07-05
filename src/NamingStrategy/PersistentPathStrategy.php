<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use function basename;
use function dirname;

/**
 * @implements NamingStrategy<\Arxy\FilesBundle\Model\PathAwareFile>
 */
final class PersistentPathStrategy implements NamingStrategy
{
    public function getDirectoryName(File $file): ?string
    {
        $pathname = $file->getPathname();

        $directory = dirname($pathname);
        if ($directory === '.') {
            return null;
        }

        return $directory.DIRECTORY_SEPARATOR;
    }

    public function getFileName(File $file): string
    {
        $pathname = $file->getPathname();

        return basename($pathname);
    }
}
