<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function ByteUnits\bytes;

class FilesExtension extends AbstractExtension
{
    private ManagerInterface $manager;

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_bytes', [$this, 'formatBytes']),
            new TwigFilter('file_content', [$this, 'readContent']),
        ];
    }

    public function readContent(File $file): string
    {
        return $this->manager->read($file);
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        return bytes($bytes)->format($precision, ' ');
    }
}
