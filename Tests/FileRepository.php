<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Repository;

class FileRepository implements Repository
{
    public function findByHashAndSize(string $hash, int $size): ?File
    {
        return null;
    }

    public function findAllForBatchProcessing(): iterable
    {
        return [];
    }
}
