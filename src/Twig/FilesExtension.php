<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\ReaderInterface;
use Twig\Attribute\AsTwigFilter;

use function ByteUnits\bytes;

/**
 * @template T of File
 */
class FilesExtension
{
    /**
     * @param ReaderInterface<T> $manager
     */
    public function __construct(
        private readonly ReaderInterface $manager
    ) {
    }

    /**
     * @param T $file
     * @throws \Arxy\FilesBundle\FileException
     */
    #[AsTwigFilter('file_content')]
    public function readContent(File $file): string
    {
        return $this->manager->read($file);
    }

    #[AsTwigFilter('format_bytes')]
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        /* @phpstan-ignore return.type (its ok) */
        return bytes($bytes)->format($precision, ' ');
    }
}
