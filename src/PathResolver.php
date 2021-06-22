<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 */
interface PathResolver
{
    /**
     * @param T $file
     * @throws UnableToResolvePath
     */
    public function getPath(File $file): string;
}
