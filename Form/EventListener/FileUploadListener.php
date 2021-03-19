<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Form\EventListener;

use Arxy\FilesBundle\ManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploadListener implements EventSubscriberInterface
{
    private ManagerInterface $fileManager;
    private bool $multiple;

    public function __construct(ManagerInterface $fileManager, bool $multiple)
    {
        $this->fileManager = $fileManager;
        $this->multiple = $multiple;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'submit',
        ];
    }

    private function transform($data)
    {
        if ($this->multiple) {
            return array_map(
                fn (UploadedFile $file) => $this->fileManager->upload($file),
                $data
            );
        } else {
            return $this->fileManager->upload($data);
        }
    }

    public function submit(FormEvent $event)
    {
        /** @var UploadedFile|UploadedFile[] $uploadedFile */
        $uploadedFile = $event->getForm()->get('file')->getData();

        if (!empty($uploadedFile)) {
            $event->setData($this->transform($uploadedFile));
        }
    }
}
