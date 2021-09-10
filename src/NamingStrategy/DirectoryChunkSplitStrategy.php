<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

use function chunk_split;
use function substr;

final class DirectoryChunkSplitStrategy implements NamingStrategy
{
    private NamingStrategy $originalStrategy;
    private int $offset;
    private int $length;
    private int $chunkSplit;

    public function __construct(NamingStrategy $originalStrategy, int $offset = 0, int $length = 3, int $chunkSplit = 1)
    {
        $this->originalStrategy = $originalStrategy;
        $this->offset = $offset;
        $this->length = $length;
        $this->chunkSplit = $chunkSplit;
    }

    public function getDirectoryName(File $file): ?string
    {
        $filename = $this->originalStrategy->getFileName($file);

        return chunk_split(substr($filename, $this->offset, $this->length), $this->chunkSplit, DIRECTORY_SEPARATOR);
    }

    public function getFileName(File $file): string
    {
        return $this->originalStrategy->getFileName($file);
    }
}
