<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Repository;

use Arxy\FilesBundle\Repository\ORM;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class ORMTest extends TestCase
{
    public function testFindByHashAndSize(): void
    {
        $mock = $this->getMockForTrait(ORM::class);

        $mock->expects($this->once())
            ->method('findOneBy')
            ->with(['hash' => 'hash', 'size' => 123456]);

        $mock->findByHashAndSize('hash', 123456);
    }

    public function testFindAllForBatchProcessing(): void
    {
        $queryMock = $this->createMock(Query::class);
        $queryMock->expects($this->once())->method('toIterable');

        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->expects($this->once())->method('getQuery')->willReturn($queryMock);

        $repository = new class ($qbMock) {
            use ORM;

            public function __construct(
                private readonly QueryBuilder $queryBuilder
            ) {
            }

            public function findOneBy(array $criteria, ?array $orderBy = null): mixed
            {
                return null;
            }

            public function createQueryBuilder(string $alias, ?string $indexBy = null): QueryBuilder
            {
                TestCase::assertSame('file', $alias);
                TestCase::assertNull($indexBy);

                return $this->queryBuilder;
            }
        };

        $repository->findAllForBatchProcessing();
    }
}
