<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

class DateStrategy implements NamingStrategy
{
    private string $format;

    public function __construct(string $format = 'Y'.DIRECTORY_SEPARATOR.'m'.DIRECTORY_SEPARATOR.'d')
    {
        $this->format = $format;
    }

    public function getDirectoryName(File $file): ?string
    {
        return $file->getCreatedAt()->format($this->format).DIRECTORY_SEPARATOR;
    }

    public function getFileName(File $file): string
    {
        return $file->getMd5Hash();
    }
}
