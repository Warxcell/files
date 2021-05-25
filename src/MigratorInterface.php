<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface MigratorInterface
{
    public function migrate(File $file): bool;
}
