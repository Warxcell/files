<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Utility\NamingStrategyUtility;

class Migrator implements MigratorInterface
{
    private MigrateableStorage $storage;
    private NamingStrategy $old;
    private NamingStrategy $new;

    public function __construct(
        MigrateableStorage $storage,
        NamingStrategy $oldNamingStrategy,
        NamingStrategy $newNamingStrategy
    ) {
        $this->storage = $storage;
        $this->old = $oldNamingStrategy;
        $this->new = $newNamingStrategy;
    }

    public function migrate(File $file): bool
    {
        $oldName = NamingStrategyUtility::getPathnameFromStrategy($this->old, $file);
        $newName = NamingStrategyUtility::getPathnameFromStrategy($this->new, $file);

        return $this->storage->migrate($file, $oldName, $newName);
    }
}
