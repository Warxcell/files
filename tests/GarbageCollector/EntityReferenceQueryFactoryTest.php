<?php

declare(strict_types=1);
/*
 * Copyright (C) 2016-2026 Taylor & Hart Limited
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains the property
 * of Taylor & Hart Limited and its suppliers, if any.
 *
 * All   intellectual   and  technical  concepts  contained  herein  are
 * proprietary  to  Taylor & Hart Limited  and  its suppliers and may be
 * covered  by  U.K.  and  foreign  patents, patents in process, and are
 * protected in full by copyright law. Dissemination of this information
 * or  reproduction  of this material is strictly forbidden unless prior
 * written permission is obtained from Taylor & Hart Limited.
 *
 * ANY  REPRODUCTION, MODIFICATION, DISTRIBUTION, PUBLIC PERFORMANCE, OR
 * PUBLIC  DISPLAY  OF  OR  THROUGH  USE OF THIS SOURCE CODE WITHOUT THE
 * EXPRESS  WRITTEN CONSENT OF RARE PINK LIMITED IS STRICTLY PROHIBITED,
 * AND  IN  VIOLATION  OF  APPLICABLE LAWS. THE RECEIPT OR POSSESSION OF
 * THIS  SOURCE CODE AND/OR RELATED INFORMATION DOES NOT CONVEY OR IMPLY
 * ANY  RIGHTS  TO REPRODUCE, DISCLOSE OR DISTRIBUTE ITS CONTENTS, OR TO
 * MANUFACTURE,  USE, OR SELL ANYTHING THAT IT MAY DESCRIBE, IN WHOLE OR
 * IN PART.
 */

namespace Arxy\FilesBundle\Tests\GarbageCollector;

use Arxy\FilesBundle\GarbageCollector\EntityReferenceQueryFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class EntityReferenceQueryFactoryTest extends TestCase
{
    public function testQuery(): void
    {
        $factory = new EntityReferenceQueryFactory($this->createEntityManager());

        self::assertSame(
            sprintf(
                'SELECT file FROM %s file WHERE NOT EXISTS (SELECT 1 FROM %s entityReference0 WHERE file MEMBER OF entityReference0.targets) AND NOT EXISTS (SELECT 1 FROM %s entityReference1 WHERE entityReference1.target = file)',
                File::class,
                EntityWithManyFiles::class,
                EntityWithSingleFile::class
            ),
            $factory->createUnreferencedQueryBuilder(File::class, 'file')->getDQL()
        );
    }

    public function testFindFilesForGarbageCollection()
    {
        $em = $this->createEntityManager();

        $file1 = new File(1);
        $em->persist($file1);

        $file2 = new File(2);
        $em->persist($file2);

        $file3 = new File(3);
        $em->persist($file3);

        $file4 = new File(4);
        $em->persist($file4);

        $entityWithSingleFile = new EntityWithSingleFile(1, $file3);
        $em->persist($entityWithSingleFile);

        $entityWithSingleFile = new EntityWithManyFiles(1, [$file4]);
        $em->persist($entityWithSingleFile);

        $em->flush();
        $em->clear();

        $factory = new EntityReferenceQueryFactory($em);
        $files = iterator_to_array($factory->findFilesForGarbageCollection(File::class));

        $this->assertCount(2, $files);
        $this->assertSame(1, $files[0]->getId());
        $this->assertSame(2, $files[1]->getId());
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $configuration = ORMSetup::createAttributeMetadataConfiguration([__DIR__], true);
        $configuration->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $entityManager = new EntityManager(
            $connection,
            $configuration,
            new EventManager()
        );

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $entityManager;
    }
}

#[ORM\Entity]
final class File
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}

#[ORM\Entity]
final class EntityWithSingleFile
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\ManyToOne(targetEntity: File::class)]
        private ?File $target,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTarget(): ?File
    {
        return $this->target;
    }
}

#[ORM\Entity]
final class EntityWithManyFiles
{
    /**
     * @param iterable<int, File> $targets
     */
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\ManyToMany(targetEntity: File::class)]
        private iterable $targets,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTargets(): iterable
    {
        return $this->targets;
    }
}
