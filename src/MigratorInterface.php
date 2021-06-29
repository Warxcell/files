<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface MigratorInterface
{
    /**
     * @throws FileException
     */
    public function migrate(File $file): bool;
}
