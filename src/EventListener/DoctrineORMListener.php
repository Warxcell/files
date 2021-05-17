<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Closure;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;

class DoctrineORMListener extends AbstractDoctrineORMListener
{
    private ManagerInterface $manager;
    private Closure $move;
    private Closure $remove;

    public function __construct(ManagerInterface $manager)
    {
        parent::__construct($manager->getClass());
        $this->manager = $manager;

        $this->move = static function (File $file) use ($manager): void {
            $manager->moveFile($file);
        };
        $this->remove = static function (File $file) use ($manager): void {
            $manager->remove($file);
        };
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
        $this->handleEmbeddable($entityManager, $entity, $this->class, $this->move);
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $entityManager = $eventArgs->getEntityManager();

        if ($this->supports($entity)) {
            ($this->remove)($entity);
        }
        $this->handleEmbeddable($entityManager, $entity, $this->class, $this->remove);
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
