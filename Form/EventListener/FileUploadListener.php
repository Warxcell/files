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
    private $data;

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
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::SUBMIT => 'submit',
        ];
    }

    public function postSetData(FormEvent $event)
    {
        $this->data = $event->getData();
    }

    public function submit(FormEvent $event)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $event->getData();

        if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
            $event->setData($this->fileManager->upload($uploadedFile));
        } else {
            $event->setData($this->data);
        }
    }
}
