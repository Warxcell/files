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

namespace Arxy\FilesBundle\GarbageCollector;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Arxy\FilesBundle\Model\File;

use function is_a;
use function sprintf;
use function str_contains;
use function usort;

final readonly class EntityReferenceQueryFactory
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param class-string $entityClass
     *
     * @return list<array{
     *     sourceEntity: class-string,
     *     fieldName: string,
     *     targetEntity: class-string,
     *     type: int,
     *     owningSide: bool
     * }>
     */
    public function findReferencesTo(string $entityClass): array
    {
        $targetMetadata = $this->entityManager->getClassMetadata($entityClass);
        $targetClass = $targetMetadata->name;
        $references = [];

        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            foreach ($metadata->associationMappings as $fieldName => $mapping) {
                if (!$this->associationTargetsEntity($mapping->targetEntity, $targetClass)) {
                    continue;
                }

                $references[] = [
                    'sourceEntity' => $metadata->name,
                    'fieldName' => $fieldName,
                    'targetEntity' => $mapping->targetEntity,
                    'type' => $mapping->type(),
                    'owningSide' => $mapping->isOwningSide(),
                ];
            }
        }

        usort(
            $references,
            static fn (array $left, array $right): int => [$left['sourceEntity'], $left['fieldName']]
                <=> [$right['sourceEntity'], $right['fieldName']]
        );

        return $references;
    }

    /**
     * Builds a query that returns target entities that are not referenced by any owning association.
     *
     * @param class-string $entityClass
     */
    public function createUnreferencedQueryBuilder(string $entityClass, string $alias): QueryBuilder
    {
        $targetMetadata = $this->entityManager->getClassMetadata($entityClass);
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select($alias)
            ->from($targetMetadata->name, $alias);

        $index = 0;

        foreach ($this->findReferencesTo($targetMetadata->name) as $reference) {
            if (!$reference['owningSide']) {
                continue;
            }

            $fieldName = $reference['fieldName'];
            $referenceAlias = 'entityReference' . $index;

            if ($this->isToOneAssociation($reference['type'])) {
                $queryBuilder->andWhere(
                    sprintf(
                        'NOT EXISTS (SELECT 1 FROM %s %s WHERE %s.%s = %s)',
                        $reference['sourceEntity'],
                        $referenceAlias,
                        $referenceAlias,
                        $fieldName,
                        $alias
                    )
                );
            } elseif ($this->isManyToManyAssociation($reference['type'])) {
                $joinAlias = 'referencedEntity' . $index;
                $queryBuilder->andWhere(
                    sprintf(
                        'NOT EXISTS (SELECT 1 FROM %s %s WHERE %s MEMBER OF %s.%s)',
                        $reference['sourceEntity'],
                        $referenceAlias,
                        $alias,
                        $referenceAlias,
                        $fieldName,
                    )
                );
            }

            ++$index;
        }

        return $queryBuilder;
    }

    /**
     * @template T of File
     * @param class-string<T> $entityClass
     * @return iterable<int, T>
     */
    public function findFilesForGarbageCollection(string $entityClass): iterable
    {
        /* @phpstan-ignore return.type (ORM is shitty) */
        return $this->createUnreferencedQueryBuilder($entityClass, 'file')->getQuery()->toIterable();
    }

    /**
     * @param class-string $targetEntity
     * @param class-string $entityClass
     */
    private function associationTargetsEntity(string $targetEntity, string $entityClass): bool
    {
        if ($targetEntity === $entityClass) {
            return true;
        }

        if (!str_contains($targetEntity, '\\')) {
            return false;
        }

        return is_a($targetEntity, $entityClass, true) || is_a($entityClass, $targetEntity, true);
    }

    private function isToOneAssociation(int $type): bool
    {
        return 0 !== ($type & ClassMetadata::TO_ONE);
    }

    private function isManyToManyAssociation(int $type): bool
    {
        return ClassMetadata::MANY_TO_MANY === $type;
    }
}
