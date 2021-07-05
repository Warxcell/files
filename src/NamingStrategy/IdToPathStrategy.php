<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use function chunk_split;

/**
 * @implements NamingStrategy<\Arxy\FilesBundle\Model\IdentifiableFile>
 * @deprecated IdToPathStrategy is deprecated. No replacement. Implement it yourself.
 */
final class IdToPathStrategy implements NamingStrategy
{
    public function getDirectoryName(File $file): ?string
    {
        return chunk_split((string)$file->getId(), 1, DIRECTORY_SEPARATOR);
    }

    public function getFileName(File $file): string
    {
        return (string)$file->getId();
    }
}
