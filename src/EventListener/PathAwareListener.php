<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Arxy\FilesBundle\Event\PostUpload;
use Arxy\FilesBundle\Model\MutablePathAware;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Utility\NamingStrategyUtility;
use Arxy\FilesBundle\Validator\Constraint\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PathAwareListener implements EventSubscriberInterface
{
    /**
     * @param NamingStrategy<MutablePathAware> $namingStrategy
     */
    public function __construct(
        private readonly NamingStrategy $namingStrategy
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            PostUpload::class => 'onUpload',
        ];
    }

    /**
     * @param PostUpload<\Arxy\FilesBundle\Model\File, mixed> $event
     */
    public function onUpload(PostUpload $event): void
    {
        $entity = $event->getFile();

        if ($entity instanceof MutablePathAware) {
            $entity->setPathname(NamingStrategyUtility::getPathnameFromStrategy($this->namingStrategy, $entity));
        }
    }
}
