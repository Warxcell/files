<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Utility\NamingStrategyUtility;
use League\Flysystem\FilesystemOperator;

class Migrator implements MigratorInterface
{
    private FilesystemOperator $filesystem;
    private NamingStrategy $old;
    private NamingStrategy $new;

    public function __construct(
        FilesystemOperator $filesystem,
        NamingStrategy $oldNamingStrategy,
        NamingStrategy $newNamingStrategy
    ) {
        $this->filesystem = $filesystem;
        $this->old = $oldNamingStrategy;
        $this->new = $newNamingStrategy;
    }

    public function migrate(File $file): bool
    {
        $oldName = NamingStrategyUtility::getPathnameFromStrategy($this->old, $file);
        if (!$this->filesystem->fileExists($oldName)) {
            return false;
        }

        $newName = NamingStrategyUtility::getPathnameFromStrategy($this->new, $file);
        $this->filesystem->move($oldName, $newName);

        return true;
    }
}
