<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Utility\NamingStrategyUtility;

class Migrator implements MigratorInterface
{
    /**
     * @param MigrateableStorage<File> $storage
     * @param NamingStrategy<File> $oldNamingStrategy
     * @param NamingStrategy<File> $newNamingStrategy
     */
    public function __construct(
        private readonly MigrateableStorage $storage,
        private readonly NamingStrategy $oldNamingStrategy,
        private readonly NamingStrategy $newNamingStrategy
    ) {
    }

    #[\Override]
    public function migrate(File $file): bool
    {
        $oldName = NamingStrategyUtility::getPathnameFromStrategy($this->oldNamingStrategy, $file);
        $newName = NamingStrategyUtility::getPathnameFromStrategy($this->newNamingStrategy, $file);

        return $this->storage->migrate($file, $oldName, $newName);
    }
}
