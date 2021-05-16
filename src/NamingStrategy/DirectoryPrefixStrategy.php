<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

final class DirectoryPrefixStrategy implements NamingStrategy
{
    private NamingStrategy $originalStrategy;
    private string $prefix;

    public function __construct(NamingStrategy $originalStrategy, string $prefix)
    {
        $this->originalStrategy = $originalStrategy;
        $this->prefix = rtrim($prefix, DIRECTORY_SEPARATOR);
    }

    public function getDirectoryName(File $file): ?string
    {
        $directory = $this->originalStrategy->getDirectoryName($file);
        if ($directory === null) {
            return $this->prefix.DIRECTORY_SEPARATOR;
        }

        return rtrim($this->prefix.DIRECTORY_SEPARATOR.$directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    public function getFileName(File $file): string
    {
        return $this->originalStrategy->getFileName($file);
    }
}
