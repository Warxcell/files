<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Closure;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;

final class DoctrineORMListener
{
    private ManagerInterface $manager;
    private string $class;
    private Closure $move;
    private Closure $remove;

    public function __construct(ManagerInterface $manager)
    {
        $this->class = $manager->getClass();
        $this->manager = $manager;

        $this->move = static function (File $file) use ($manager): void {
            $manager->moveFile($file);
        };
        $this->remove = static function (File $file) use ($manager): void {
            $manager->remove($file);
        };
    }

    final protected function supports(object $entity): bool
    {
        return $entity instanceof $this->class;
    }

    final protected function handleEmbeddable(
        EntityManagerInterface $entityManager,
        object $entity,
        Closure $action
    ): void {
        $classMetadata = $entityManager->getClassMetadata(ClassUtils::getClass($entity));

        foreach ($classMetadata->embeddedClasses as $property => $embeddedClass) {
            if (!is_a($embeddedClass['class'], $this->class, true)) {
                continue;
            }

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

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $entityManager = $eventArgs->getEntityManager();
        if ($this->supports($entity)) {
            try {
                ($this->move)($entity);
            } catch (InvalidArgumentException $exception) {
                // file doesn't exists in FileMap.
            }
        }
        $this->handleEmbeddable($entityManager, $entity, $this->move);
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $entityManager = $eventArgs->getEntityManager();

        if ($this->supports($entity)) {
            ($this->remove)($entity);
        }
        $this->handleEmbeddable($entityManager, $entity, $this->remove);
    }

    public function postRemove(LifecycleEventArgs $eventArgs)
    {
        $this->preRemove($eventArgs);
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->manager->clear();
    }
}
