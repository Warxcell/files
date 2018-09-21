<?php
declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface NamingStrategy
{
    public function getDirectoryName(File $file): string;

    public function getFileName(File $file): string;
}