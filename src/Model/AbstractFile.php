<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;
use Symfony\Component\Clock\DatePoint;

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
        /* @phpstan-ignore missingType.checkedException (do you see string somewhere?) */
        $this->createdAt = new DatePoint();
    }

    #[\Override]
    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    #[\Override]
    public function getSize(): int
    {
        return $this->size;
    }

    #[\Override]
    public function getHash(): string
    {
        return $this->hash;
    }

    #[\Override]
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[\Override]
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
