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
    /**
     * @param ManagerInterface<File, mixed> $fileManager
     */
    public function __construct(
        private readonly ManagerInterface $fileManager,
        private readonly bool $multiple
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'submit',
        ];
    }

    /**
     * @throws \Arxy\FilesBundle\UnableToUpload
     * @throws \Symfony\Component\Form\Exception\OutOfBoundsException
     * @throws \Symfony\Component\Form\Exception\RuntimeException
     */
    public function submit(FormEvent $event): void
    {
        /** @var SplFileInfo|SplFileInfo[]|null $uploadedFile */
        $uploadedFile = $event->getForm()->get('file')->getData();

        if ($uploadedFile !== null) {
            $event->setData($this->transform($uploadedFile));
        }
    }

    /**
     * @param SplFileInfo|SplFileInfo[] $data
     * @return File|File[]
     * @throws \Arxy\FilesBundle\UnableToUpload
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
}
