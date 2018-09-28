<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Twig\Extension\AbstractExtension;

class FilesExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter(
                'format_bytes', [$this, 'formatBytes']
            ),
        ];
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KiB', 'MiB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
