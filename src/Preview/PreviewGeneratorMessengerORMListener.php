<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

class PreviewGeneratorMessengerORMListener
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof PreviewableFile) {
            $this->bus->dispatch(new GeneratePreviewMessage($entity));
        }
    }
}