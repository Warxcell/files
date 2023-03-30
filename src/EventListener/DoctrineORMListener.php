<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Event\TransactionCommitEventArgs;
use Doctrine\DBAL\Event\TransactionRollBackEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use ReflectionObject;

final class DoctrineORMListener
{
    private ManagerInterface $manager;
    /** @var class-string<File> */
    private string $class;
    private array $pendingMove = [];
    private array $pendingRemove = [];

    public function __construct(ManagerInterface $manager)
    {
        $this->class = $manager->getClass();
        $this->manager = $manager;
    }

    public function postPersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getEntity();
        $entityManager = $eventArgs->getEntityManager();
        if ($this->supports($entity)) {
            $this->pendingMove[] = $entity;
        }
        foreach ($this->handleEmbeddable($entityManager, $entity) as $file) {
            $this->pendingMove[] = $file;
        }
    }

    public function postRemove(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getEntity();
        $entityManager = $eventArgs->getEntityManager();

        if ($this->supports($entity)) {
            $this->pendingRemove[] = $entity;
        }
        foreach ($this->handleEmbeddable($entityManager, $entity) as $file) {
            $this->pendingRemove[] = $file;
        }
    }

    public function onTransactionCommit(TransactionCommitEventArgs $eventArgs): void
    {
        if ($eventArgs->getConnection()->isTransactionActive()) {
            return;
        }

        $pendingMove = $this->pendingMove;
        foreach ($pendingMove as $file) {
            $this->manager->moveFile($file);
        }

        $pendingRemove = $this->pendingRemove;
        foreach ($pendingRemove as $file) {
            $this->manager->remove($file);
        }

        $this->clearPending();
    }

    public function onTransactionRollBack(TransactionRollBackEventArgs $eventArgs): void
    {
        if ($eventArgs->getConnection()->isTransactionActive()) {
            return;
        }

        $this->clearPending();
    }

    private function clearPending(): void
    {
        $this->pendingMove = [];
        $this->pendingRemove = [];
    }

    public function onClear(OnClearEventArgs $eventArgs): void
    {
        if ($eventArgs->getEntityManager()->getConnection()->isTransactionActive()) {
            return;
        }
        $this->manager->clear();
        $this->clearPending();
    }

    private function supports(object $entity): bool
    {
        return $entity instanceof $this->class;
    }

    private function handleEmbeddable(EntityManagerInterface $entityManager, object $entity): iterable
    {
        $classMetadata = $entityManager->getClassMetadata(ClassUtils::getClass($entity));

        foreach ($classMetadata->embeddedClasses as $property => $embeddedClass) {
            if (!is_a($embeddedClass['class'], $this->class, true)) {
                continue;
            }

            $refl = new ReflectionObject($entity);
            $reflProperty = $refl->getProperty($property);
            $reflProperty->setAccessible(true);
            /** @var File|null $file */
            $file = $reflProperty->getValue($entity);

            if ($file === null) {
                continue;
            }
            yield $file;
        }
    }
}
