<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Repository;

use Arxy\FilesBundle\Model\File;
use Doctrine\ORM\QueryBuilder;

trait ORM
{
    /** @return QueryBuilder */
    abstract public function createQueryBuilder($alias, $indexBy = null);

    abstract public function findOneBy(array $criteria, array $orderBy = null);

    public function findByHashAndSize(string $hash, int $size): ?File
    {
        return $this->findOneBy(
            [
                'hash' => $hash,
                'fileSize' => $size,
            ]
        );
    }

    public function findAllForBatchProcessing(): iterable
    {
        $query = $this->createQueryBuilder('file')->getQuery();

        return $query->toIterable();
    }
}
