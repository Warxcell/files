<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PathResolverExtension extends AbstractExtension
{
    private PathResolver $pathResolver;

    public function __construct(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_path', [$this, 'filePath']),
        ];
    }

    public function filePath(File $file): string
    {
        return $this->pathResolver->getPath($file);
    }
}
