<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

abstract class AbstractFile implements File
{
    protected string $originalFilename;
    protected int $size;
    protected string $hash;
    protected DateTimeImmutable $createdAt;
    protected string $mimeType;

    public function __construct(string $originalFilename, int $size, string $hash, string $mimeType)
    {
        $this->originalFilename = $originalFilename;
        $this->size = $size;
        $this->hash = $hash;
        $this->mimeType = $mimeType;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getSize(): int
    {
        return $this->size;
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
