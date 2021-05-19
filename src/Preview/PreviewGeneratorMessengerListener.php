<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Event\FileUploaded;
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
            FileUploaded::class => 'generatePreview',
        ];
    }

    public function generatePreview(FileUploaded $event): void
    {
        $file = $event->getFile();

        if ($file instanceof PreviewableFile) {
            $this->bus->dispatch(new GeneratePreviewMessage($file));
        }
    }
}
