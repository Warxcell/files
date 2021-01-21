<?php
declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface Repository
{
    public function findByHashAndSize(string $hash, int $size): ?File;

    /** @return \Generator|File[] */
    public function findAllForBatchProcessing(): \Generator;
}