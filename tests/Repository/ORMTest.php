<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Repository;

use Arxy\FilesBundle\Repository\ORM;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class ORMTest extends TestCase
{
    public function testFindByHashAndSize()
    {
        $mock = $this->getMockForTrait(ORM::class);

        $mock->expects($this->once())
            ->method('findOneBy')
            ->with(['md5Hash' => 'hash', 'fileSize' => 123456]);

        $mock->findByHashAndSize('hash', 123456);
    }

    public function testFindAllForBatchProcessing()
    {
        $mock = $this->getMockForTrait(ORM::class);

        $queryMock = $this->createMock(AbstractQuery::class);
        $queryMock->expects($this->once())->method('toIterable');

        $qbMock = $this->createMock(QueryBuilder::class);
        $qbMock->expects($this->once())->method('getQuery')->willReturn($queryMock);

        $mock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qbMock);

        $mock->findAllForBatchProcessing();
    }
}