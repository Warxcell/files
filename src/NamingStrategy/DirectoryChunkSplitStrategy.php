<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

use function chunk_split;
use function substr;

/**
 * @template T of File
 * @implements NamingStrategy<T>
 */
final class DirectoryChunkSplitStrategy implements NamingStrategy
{
    /**
     * @param NamingStrategy<T> $originalStrategy
     * @param int<1, max> $chunkSplit
     */
    public function __construct(
        private readonly NamingStrategy $originalStrategy,
        private readonly int $offset = 0,
        private readonly int $length = 3,
        private readonly int $chunkSplit = 1
    ) {
    }

    #[\Override]
    public function getDirectoryName(File $file): string
    {
        $filename = $this->originalStrategy->getFileName($file);

        return chunk_split(substr($filename, $this->offset, $this->length), $this->chunkSplit, DIRECTORY_SEPARATOR);
    }

    #[\Override]
    public function getFileName(File $file): string
    {
        return $this->originalStrategy->getFileName($file);
    }
}
