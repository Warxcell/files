<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Entity\File as AbstractFile;
use DateTimeImmutable;

class MutableFile extends AbstractFile implements \Arxy\FilesBundle\Model\MutableFile
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

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
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
