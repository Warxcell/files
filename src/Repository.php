<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 */
interface Repository
{
    /**
     * @return T|null
     */
    public function findByHashAndSize(string $hash, int $size): ?File;

    /** @return T[] */
    public function findAllForBatchProcessing(): iterable;
}
