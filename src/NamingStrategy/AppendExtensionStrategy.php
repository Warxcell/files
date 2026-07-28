<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

use function pathinfo;

/**
 * @template T of File
 * @implements NamingStrategy<T>
 */
final class AppendExtensionStrategy implements NamingStrategy
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
        return $this->originalStrategy->getDirectoryName($file);
    }

    #[\Override]
    public function getFileName(File $file): string
    {
        $extension = pathinfo($file->getOriginalFilename(), PATHINFO_EXTENSION);

        return $this->originalStrategy->getFileName($file) . '.' . $extension;
    }
}
