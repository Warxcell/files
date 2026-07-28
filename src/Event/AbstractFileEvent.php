<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Event;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractFileEvent extends Event
{
    /**
     * @param ManagerInterface<File, mixed> $manager
     * @param File $file
     */
    public function __construct(
        private readonly ManagerInterface $manager,
        private readonly File $file
    ) {
    }

    public function getManager(): ManagerInterface
    {
        return $this->manager;
    }

    public function getFile(): File
    {
        return $this->file;
    }
}
