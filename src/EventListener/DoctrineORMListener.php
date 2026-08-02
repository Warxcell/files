<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\FileException;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use ReflectionException;
use ReflectionObject;

final class DoctrineORMListener
{
    private string $class;

    /**
     * @param ManagerInterface<File, mixed> $manager
     */
    public function __construct(
        private readonly ManagerInterface $manager
    ) {
        $this->class = $manager->getClass();
    }

    /**
     * @throws ReflectionException
     * @throws FileException
     */
    public function postPersist(PostPersistEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        $entityManager = $eventArgs->getObjectManager();
        if ($this->supports($entity)) {
            $this->manager->moveFile($entity);
        }
        foreach ($this->handleEmbeddable($entityManager, $entity) as $file) {
            $this->manager->moveFile($file);
        }
    }

    /**
     * @throws ReflectionException
     * @throws FileException
     */
    public function postRemove(PostRemoveEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        $entityManager = $eventArgs->getObjectManager();

        if ($this->supports($entity)) {
            $this->manager->remove($entity);
        }
        foreach ($this->handleEmbeddable($entityManager, $entity) as $file) {
            $this->manager->remove($file);
        }
    }

    public function onClear(): void
    {
        $this->manager->clear();
    }

    /**
     * @phpstan-assert-if-true File $entity
     */
    private function supports(object $entity): bool
    {
        return $entity instanceof $this->class;
    }

    /**
     * @return iterable<File>
     * @throws ReflectionException
     */
    private function handleEmbeddable(
        EntityManagerInterface $entityManager,
        object $entity,
    ): iterable {
        $classMetadata = $entityManager->getClassMetadata($entity::class);

        foreach ($classMetadata->embeddedClasses as $property => $embeddedClass) {
            if (!is_a($embeddedClass->class, $this->class, true)) {
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
