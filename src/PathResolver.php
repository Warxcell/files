<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface PathResolver
{
    /**
     * @throws \Arxy\FilesBundle\InvalidArgumentException
     */
    public function getPath(File $file): string;
}
