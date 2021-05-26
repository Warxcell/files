<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use function pathinfo;

final class AppendExtensionStrategy implements NamingStrategy
{
    private NamingStrategy $originalStrategy;

    public function __construct(NamingStrategy $originalStrategy)
    {
        $this->originalStrategy = $originalStrategy;
    }

    public function getDirectoryName(File $file): ?string
    {
        return $this->originalStrategy->getDirectoryName($file);
    }

    public function getFileName(File $file): string
    {
        $extension = pathinfo($file->getOriginalFilename(), PATHINFO_EXTENSION);

        return $this->originalStrategy->getFileName($file).'.'.$extension;
    }
}
