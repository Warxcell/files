<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Doctrine\ORM\Event\LifecycleEventArgs;

class PreviewGeneratorORMListener
{
    private PreviewGenerator $previewGenerator;

    public function __construct(PreviewGenerator $previewGenerator)
    {
        $this->previewGenerator = $previewGenerator;
    }

    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        if ($entity instanceof PreviewableFile) {
            try {
                $entity->setPreview($this->previewGenerator->generate($entity));
            } catch (NoPreviewGeneratorFound $exception) {
            }
        }
    }
}