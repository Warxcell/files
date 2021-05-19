<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\Event\FileUploaded;
use Arxy\FilesBundle\Model\PathAwareFile;
use Arxy\FilesBundle\NamingStrategy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PathAwareDoctrineListener implements EventSubscriberInterface
{
    private NamingStrategy $namingStrategy;

    public function __construct(NamingStrategy $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FileUploaded::class => 'onUpload',
        ];
    }

    private function getPathname(PathAwareFile $file): string
    {
        return ($this->namingStrategy->getDirectoryName($file) ?? "").$this->namingStrategy->getFileName($file);
    }

    public function onUpload(FileUploaded $event): void
    {
        $entity = $event->getFile();

        if ($entity instanceof PathAwareFile) {
            $entity->setPathname($this->getPathname($entity));
        }
    }
}
