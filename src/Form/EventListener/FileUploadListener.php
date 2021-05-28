<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Form\EventListener;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FileUploadListener implements EventSubscriberInterface
{
    private ManagerInterface $fileManager;
    private bool $multiple;

    public function __construct(ManagerInterface $fileManager, bool $multiple)
    {
        $this->fileManager = $fileManager;
        $this->multiple = $multiple;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'submit',
        ];
    }

    /**
     * @param SplFileInfo|SplFileInfo[] $data
     * @return File|File[]
     */
    private function transform($data)
    {
        if ($this->multiple) {
            /** @var SplFileInfo[] $data */
            return array_map(
                fn (SplFileInfo $file): File => $this->fileManager->upload($file),
                $data
            );
        } else {
            /** @var SplFileInfo $data */
            return $this->fileManager->upload($data);
        }
    }

    public function submit(FormEvent $event): void
    {
        /** @var SplFileInfo|SplFileInfo[] $uploadedFile */
        $uploadedFile = $event->getForm()->get('file')->getData();

        if (!empty($uploadedFile)) {
            $event->setData($this->transform($uploadedFile));
        }
    }
}
