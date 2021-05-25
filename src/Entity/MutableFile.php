<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Entity;

use Arxy\FilesBundle\Model\AbstractFile;
use Arxy\FilesBundle\Model\IdentifiableFile;
use DateTimeImmutable;

abstract class MutableFile extends AbstractFile implements IdentifiableFile, \Arxy\FilesBundle\Model\MutableFile
{
    abstract public function getId();

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function setMd5Hash(string $md5Hash): void
    {
        $this->md5Hash = $md5Hash;
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
