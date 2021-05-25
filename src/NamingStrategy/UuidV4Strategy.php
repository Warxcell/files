<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use Symfony\Component\Uid\Uuid;

/**
 * UUID V4 - Random UUID. Usable only with PersistentPathStrategy - so once filename is generated - it should be persisted in DB.
 */
final class UuidV4Strategy implements NamingStrategy
{
    public function getDirectoryName(File $file): ?string
    {
        return null;
    }

    public function getFileName(File $file): string
    {
        return (string)Uuid::v4();
    }
}
