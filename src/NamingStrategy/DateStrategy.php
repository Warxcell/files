<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

/**
 * @implements NamingStrategy<File>
 */
final class DateStrategy implements NamingStrategy
{
    private const DEFAULT_FORMAT = 'Y' . DIRECTORY_SEPARATOR . 'm' . DIRECTORY_SEPARATOR . 'd';

    public function __construct(
        private readonly string $format = self::DEFAULT_FORMAT
    ) {
    }

    #[\Override]
    public function getDirectoryName(File $file): string
    {
        return $file->getCreatedAt()->format($this->format) . DIRECTORY_SEPARATOR;
    }

    #[\Override]
    public function getFileName(File $file): string
    {
        return $file->getHash();
    }
}
