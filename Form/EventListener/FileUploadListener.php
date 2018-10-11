<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Form\EventListener;

use Arxy\FilesBundle\Manager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadListener implements EventSubscriberInterface
{
    /** @var Manager */
    private $fileManager;

    /**
     * FileUploadListener constructor.
     *
     * @param Manager $fileManager
     */
    public function __construct(Manager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'submit',
        ];
    }

    public function submit(FormEvent $event)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $event->getForm()->get('file')->getData();

        if ($uploadedFile && $uploadedFile->isValid()) {
            $event->setData($this->fileManager->upload($uploadedFile));
        }
    }
}
