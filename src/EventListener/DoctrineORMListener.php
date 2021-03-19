<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;

class DoctrineORMListener implements EventSubscriber
{
    private ManagerInterface $manager;

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'preRemove',
            'onClear',
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $class = $this->manager->getClass();
        if ($entity instanceof File && $entity instanceof $class) {
            $this->manager->moveFile($entity);
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $class = $this->manager->getClass();
        if ($entity instanceof File && $entity instanceof $class) {
            $this->manager->remove($entity);
        }
    }

    public function onClear(OnClearEventArgs $args)
    {
        $this->manager->clear();
    }
}
