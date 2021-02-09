<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

class File extends \Arxy\FilesBundle\Model\File
{
    /** @var int|null */
    private $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}