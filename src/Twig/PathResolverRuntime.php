<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\UnableToResolvePath;
use Twig\Extension\RuntimeExtensionInterface;

class PathResolverRuntime implements RuntimeExtensionInterface
{
    /**
     * @param PathResolver<File> $pathResolver
     */
    public function __construct(
        private readonly PathResolver $pathResolver
    ) {
    }

    /**
     * @throws UnableToResolvePath
     */
    public function filePath(File $file): string
    {
        return $this->pathResolver->getPath($file);
    }
}
