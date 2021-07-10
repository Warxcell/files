<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PathResolverExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_path', [PathResolverRuntime::class, 'filePath']),
        ];
    }
}
