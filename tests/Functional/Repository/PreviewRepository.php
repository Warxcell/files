<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Repository;

use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Tests\Functional\Entity\Preview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PreviewRepository extends ServiceEntityRepository implements Repository
{
    use Repository\ORM;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Preview::class);
    }
}
