<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

class File extends MutableFile
{
    private ?int $id = null;

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
