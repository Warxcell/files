<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of \Arxy\FilesBundle\Model\File
 */
interface NamingStrategy
{
    /**
     * @param T $file
     */
    public function getDirectoryName(File $file): ?string;

    /**
     * @param T $file
     */
    public function getFileName(File $file): string;
}
