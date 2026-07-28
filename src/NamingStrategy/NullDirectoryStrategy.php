<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

/**
 * @template T of File
 * @implements NamingStrategy<T>
 */
final class NullDirectoryStrategy implements NamingStrategy
{
    /**
     * @param NamingStrategy<T> $originalStrategy
     */
    public function __construct(
        private readonly NamingStrategy $originalStrategy
    ) {
    }

    #[\Override]
    public function getDirectoryName(File $file): ?string
    {
        return null;
    }

    #[\Override]
    public function getFileName(File $file): string
    {
        return $this->originalStrategy->getFileName($file);
    }
}
