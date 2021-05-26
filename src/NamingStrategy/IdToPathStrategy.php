<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\IdentifiableFile;
use Arxy\FilesBundle\NamingStrategy;
use RuntimeException;
use function chunk_split;

final class IdToPathStrategy implements NamingStrategy
{
    private function getId(IdentifiableFile $file): string
    {
        $id = $file->getId();

        if ($id === null) {
            throw new RuntimeException('getId() returned null');
        }

        return (string)$id;
    }

    public function getDirectoryName(File $file): ?string
    {
        if (!$file instanceof IdentifiableFile) {
            throw InvalidArgumentException::invalidType($file, IdentifiableFile::class);
        }

        return chunk_split($this->getId($file), 1, DIRECTORY_SEPARATOR);
    }

    public function getFileName(File $file): string
    {
        if (!$file instanceof IdentifiableFile) {
            throw InvalidArgumentException::invalidType($file, IdentifiableFile::class);
        }

        return $this->getId($file);
    }
}
