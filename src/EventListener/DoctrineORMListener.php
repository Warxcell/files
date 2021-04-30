<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Closure;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

class DoctrineORMListener
{
    private ManagerInterface $manager;
    private string $class;
    private Closure $move;
    private Closure $remove;

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
        $this->class = $this->manager->getClass();

        $this->move = static function (File $file) use ($manager) {
            $manager->moveFile($file);
        };
        $this->remove = static function (File $file) use ($manager) {
            $manager->remove($file);
        };
    }

    private function supports(object $entity): bool
    {
        return $entity instanceof $this->class;
    }

    private function handleEmbeddable(EntityManagerInterface $entityManager, object $entity, Closure $action): void
    {
        $classMetadata = $entityManager->getClassMetadata(ClassUtils::getClass($entity));

        foreach ($classMetadata->embeddedClasses as $property => $embeddedClass) {
            if (is_a($embeddedClass['class'], $this->class, true)) {
                $refl = new \ReflectionObject($entity);
                $reflProperty = $refl->getProperty($property);
                $reflProperty->setAccessible(true);
                $file = $reflProperty->getValue($entity);

                if ($file === null) {
                    continue;
                }
                $action($file);
            }
        }
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->supports($entity)) {
                try {
                    $this->manager->moveFile($entity);
                } catch (InvalidArgumentException $exception) {
                    // file doesn't exists in FileMap.
                }
            }
            $this->handleEmbeddable($entityManager, $entity, $this->move);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($this->supports($entity)) {
                $this->manager->remove($entity);
            }
            $this->handleEmbeddable($entityManager, $entity, $this->remove);
        }
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->manager->clear();
    }
}
