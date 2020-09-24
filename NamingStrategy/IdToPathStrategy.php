<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

class IdToPathStrategy implements NamingStrategy
{
    public function getDirectoryName(File $file): string
    {
        $id = (string)$file->getId();
        $path = str_split($id);

        return implode(DIRECTORY_SEPARATOR, $path).DIRECTORY_SEPARATOR;
    }

    public function getFileName(File $file): string
    {
        return (string)$file->getId();
    }
}
