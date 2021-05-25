<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\Event\PostUpload;
use Arxy\FilesBundle\Model\MutablePathAware;
use Arxy\FilesBundle\Model\PathAwareFile;
use Arxy\FilesBundle\NamingStrategy;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PathAwareListener implements EventSubscriberInterface
{
    private NamingStrategy $namingStrategy;

    public function __construct(NamingStrategy $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostUpload::class => 'onUpload',
        ];
    }

    private function getPathname(PathAwareFile $file): string
    {
        return ($this->namingStrategy->getDirectoryName($file) ?? "").$this->namingStrategy->getFileName($file);
    }

    public function onUpload(PostUpload $event): void
    {
        $entity = $event->getFile();

        if ($entity instanceof MutablePathAware) {
            $entity->setPathname($this->getPathname($entity));
        }
    }
}
