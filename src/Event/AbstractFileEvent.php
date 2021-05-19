<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Event;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractFileEvent extends Event
{
    private ManagerInterface $manager;
    private File $file;

    public function __construct(ManagerInterface $manager, File $file)
    {
        $this->manager = $manager;
        $this->file = $file;
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
