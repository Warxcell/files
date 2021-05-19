<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Event\FileUploaded;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PreviewGeneratorListener implements EventSubscriberInterface
{
    private PreviewGenerator $previewGenerator;

    public function __construct(PreviewGenerator $previewGenerator)
    {
        $this->previewGenerator = $previewGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FileUploaded::class => 'generatePreview',
        ];
    }

    public function generatePreview(FileUploaded $fileUploaded)
    {
        $entity = $fileUploaded->getFile();

        if ($entity instanceof PreviewableFile) {
            try {
                $entity->setPreview($this->previewGenerator->generate($entity));
            } catch (NoPreviewGeneratorFound $exception) {
            }
        }
    }
}