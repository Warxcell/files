<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Twig\Extension\RuntimeExtensionInterface;

class PathResolverRuntime implements RuntimeExtensionInterface
{
    private PathResolver $pathResolver;

    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function filePath(File $file): string
    {
        return $this->pathResolver->getPath($file);
    }
}
