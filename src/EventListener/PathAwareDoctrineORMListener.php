<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\PathAwareFile;
use Arxy\FilesBundle\NamingStrategy;
use Doctrine\ORM\Event\LifecycleEventArgs;

class PathAwareDoctrineORMListener extends AbstractDoctrineORMListener
{
    private NamingStrategy $namingStrategy;

    public function __construct(ManagerInterface $manager, NamingStrategy $namingStrategy)
    {
        parent::__construct($manager->getClass());
        $this->namingStrategy = $namingStrategy;
    }

    private function getPathname(PathAwareFile $file): string
    {
        return ($this->namingStrategy->getDirectoryName($file) ?? "").$this->namingStrategy->getFileName($file);
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof PathAwareFile) {
            $entity->setPathname($this->getPathname($entity));
        }

        $this->handleEmbeddable(
            $eventArgs->getEntityManager(),
            $entity,
            PathAwareFile::class,
            function (PathAwareFile $file): void {
                $file->setPathname($this->getPathname($file));
            }
        );
    }
}
