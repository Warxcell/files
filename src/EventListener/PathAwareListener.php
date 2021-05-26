<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\Event\PostUpload;
use Arxy\FilesBundle\Model\MutablePathAware;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Utility\NamingStrategyUtility;
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

    public function onUpload(PostUpload $event): void
    {
        $entity = $event->getFile();

        if ($entity instanceof MutablePathAware) {
            $entity->setPathname(NamingStrategyUtility::getPathnameFromStrategy($this->namingStrategy, $entity));
        }
    }
}
