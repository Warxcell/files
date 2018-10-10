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

        $path = [];
        for ($i = 0; $i < mb_strlen($id); $i++) {
            $path[] = mb_substr($id, $i, 1);
        }

        return implode('/', $path).'/';
    }

    public function getFileName(File $file): string
    {
        return (string)$file->getId();
    }
}
