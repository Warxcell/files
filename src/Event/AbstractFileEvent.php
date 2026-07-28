<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Event;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @template T of File
 * @template C
 */
abstract class AbstractFileEvent extends Event
{
    /**
     * @param ManagerInterface<T, C> $manager
     * @param T $file
     */
    public function __construct(
        private readonly ManagerInterface $manager,
        private readonly File $file
    ) {
    }

    /**
     * @return ManagerInterface<T, C>
     */
    public function getManager(): ManagerInterface
    {
        return $this->manager;
    }

    /**
     * @return T
     */
    public function getFile(): File
    {
        return $this->file;
    }
}
