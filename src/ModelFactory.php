<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use SplFileInfo;

/**
 * @template T of File
 * @template C
 */
interface ModelFactory
{
    /**
     * @param C $context
     * @return T
     */
    public function create(
        SplFileInfo $file,
        string $originalFilename,
        int $size,
        string $hash,
        string $mimeType,
        mixed $context = null
    ): File;
}
