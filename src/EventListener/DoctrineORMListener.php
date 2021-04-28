<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

class DoctrineORMListener implements EventSubscriber
{
    private ManagerInterface $manager;
    private string $class;

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
        $this->class = $this->manager->getClass();
    }

    public function getSubscribedEvents(): array
    {
        return [
            'postPersist',
            'preRemove',
            'onClear',
        ];
    }

    private function supports($entity): bool
    {
        return $entity instanceof $this->class;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->supports($entity)) {
                $this->manager->moveFile($entity);
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($this->supports($entity)) {
                $this->manager->remove($entity);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($this->supports($entity)) {
            assert($entity instanceof File);
            $this->manager->moveFile($entity);
        }
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($this->supports($entity)) {
            assert($entity instanceof File);
            $this->manager->remove($entity);
        }
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->manager->clear();
    }
}
