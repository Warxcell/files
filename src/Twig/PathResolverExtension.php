<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\UnableToResolvePath;
use Twig\Attribute\AsTwigFunction;

class PathResolverExtension
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
    #[AsTwigFunction('file_path')]
    public function filePath(File $file): string
    {
        return $this->pathResolver->getPath($file);
    }
}
