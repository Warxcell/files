<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 * @extends Storage<T>
 */
interface MigrateableStorage extends Storage
{
    /**
     * @param T $file
     * @throws FileException when file cannot be migrated for some reason
     */
    public function migrate(File $file, string $oldPathname, string $newPathname): bool;
}
