<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use SplFileInfo;

interface ModelFactory
{
    public function create(
        SplFileInfo $file,
        string $originalFilename,
        int $fileSize,
        string $md5Hash,
        string $mimeType
    ): File;
}
