<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Event\PostUpdate;
use Arxy\FilesBundle\Event\PostUpload;
use Arxy\FilesBundle\Validator\Constraint\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PreviewGeneratorListener implements EventSubscriberInterface
{
    private PreviewGenerator $previewGenerator;

    public function __construct(PreviewGenerator $previewGenerator)
    {
        $this->previewGenerator = $previewGenerator;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            PostUpload::class => 'postUpload',
            PostUpdate::class => 'postUpdate',
        ];
    }

    public function postUpload(PostUpload $event): void
    {
        $entity = $event->getFile();

        if ($entity instanceof PreviewableFile) {
            $this->generatePreview($entity);
        }
    }

    public function postUpdate(PostUpdate $event): void
    {
        $entity = $event->getFile();

        if ($entity instanceof PreviewableFile) {
            $this->generatePreview($entity);
        }
    }

    /**
     * @param PreviewableFile<File> $file
     */
    private function generatePreview(PreviewableFile $file): void
    {
        try {
            $file->setPreview($this->previewGenerator->generate($file));
        } catch (NoPreviewGeneratorFound) {
        }
    }
}
