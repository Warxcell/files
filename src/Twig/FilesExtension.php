<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use function ByteUnits\bytes;

class FilesExtension extends AbstractExtension
{
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'format_bytes',
                static fn (int $bytes, int $precision = 2) => bytes($bytes)->format($precision, ' ')
            ),
            new TwigFilter('file_content', [FilesRuntime::class, 'readContent']),
        ];
    }
}
