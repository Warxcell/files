<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface ModelFactory
{
    public function create(string $originalFilename, int $fileSize, string $md5Hash, string $mimeType): File;
}