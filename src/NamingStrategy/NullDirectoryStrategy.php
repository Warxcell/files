<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

final class NullDirectoryStrategy implements NamingStrategy
{
    private NamingStrategy $originalStrategy;

    public function __construct(NamingStrategy $originalStrategy)
    {
        $this->originalStrategy = $originalStrategy;
    }

    public function getDirectoryName(File $file): ?string
    {
        return null;
    }

    public function getFileName(File $file): string
    {
        return $this->originalStrategy->getFileName($file);
    }
}
