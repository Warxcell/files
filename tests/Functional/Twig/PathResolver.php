<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Twig;

use Arxy\FilesBundle\Model\File;

class PathResolver implements \Arxy\FilesBundle\PathResolver
{
    public function getPath(File $file): string
    {
        return $file->getHash();
    }
}
