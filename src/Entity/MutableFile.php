<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Entity;

use Arxy\FilesBundle\Model\IdentifiableFile;
use DateTimeImmutable;

abstract class MutableFile extends File implements IdentifiableFile, \Arxy\FilesBundle\Model\MutableFile
{
    abstract public function getId();

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
