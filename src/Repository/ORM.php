<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Repository;

use Arxy\FilesBundle\Model\File;
use Doctrine\ORM\QueryBuilder;

trait ORM
{
    public function findByHashAndSize(string $hash, int $size): ?File
    {
        return $this->findOneBy(
            [
                'hash' => $hash,
                'size' => $size,
            ]
        );
    }

    abstract public function findOneBy(array $criteria, array $orderBy = null);

    public function findAllForBatchProcessing(): iterable
    {
        $query = $this->createQueryBuilder('file')->getQuery();

        return $query->toIterable();
    }

    /** @return QueryBuilder */
    abstract public function createQueryBuilder($alias, $indexBy = null);
}
