<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

abstract class AbstractFile implements File
{
    protected string $originalFilename;
    protected int $fileSize;
    protected string $hash;
    protected DateTimeImmutable $createdAt;
    protected string $mimeType;

    public function __construct(string $originalFilename, int $fileSize, string $hash, string $mimeType)
    {
        $this->originalFilename = $originalFilename;
        $this->fileSize = $fileSize;
        $this->hash = $hash;
        $this->mimeType = $mimeType;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
