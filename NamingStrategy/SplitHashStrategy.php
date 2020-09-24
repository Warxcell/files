<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

class SplitHashStrategy implements NamingStrategy
{
    private int $splitLength;

    public function __construct(int $splitLength = 4)
    {
        if (32 % $splitLength !== 0) {
            throw new \InvalidArgumentException('$splitEvery parameter must be modulus of 32');
        }

        $this->splitLength = $splitLength;
    }

    public function getDirectoryName(File $file): string
    {
        $hash = $file->getMd5Hash();

        $path = str_split($hash, $this->splitLength);

        return implode(DIRECTORY_SEPARATOR, $path).DIRECTORY_SEPARATOR;
    }

    public function getFileName(File $file): string
    {
        return $file->getMd5Hash();
    }
}
