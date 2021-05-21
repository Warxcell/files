<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

abstract class AbstractFile implements File, MutableFile
{
    protected string $originalFilename;
    protected int $fileSize;
    protected string $md5Hash;
    protected DateTimeImmutable $createdAt;
    protected string $mimeType;

    public function __construct(string $originalFilename, int $fileSize, string $md5Hash, string $mimeType)
    {
        $this->setOriginalFilename($originalFilename);
        $this->setFileSize($fileSize);
        $this->setMd5Hash($md5Hash);
        $this->setMimeType($mimeType);
        $this->setCreatedAt(new DateTimeImmutable());
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getMd5Hash(): string
    {
        return $this->md5Hash;
    }

    public function setMd5Hash(string $md5Hash): void
    {
        $this->md5Hash = $md5Hash;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
