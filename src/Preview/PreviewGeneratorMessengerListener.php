<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Event\PostUpdate;
use Arxy\FilesBundle\Event\PostUpload;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PreviewGeneratorMessengerListener implements EventSubscriberInterface
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostUpload::class => 'postUpload',
            PostUpdate::class => 'postUpdate',
        ];
    }

    public function postUpload(PostUpload $event): void
    {
        $file = $event->getFile();

        if ($file instanceof PreviewableFile) {
            $this->bus->dispatch(new GeneratePreviewMessage($file));
        }
    }

    public function postUpdate(PostUpdate $event): void
    {
        $file = $event->getFile();

        if ($file instanceof PreviewableFile) {
            $this->bus->dispatch(new GeneratePreviewMessage($file));
        }
    }
}
