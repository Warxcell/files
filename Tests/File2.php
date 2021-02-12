<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

class File2 extends \Arxy\FilesBundle\Model\File
{
    private ?int $id = null;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }
}