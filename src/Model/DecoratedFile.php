<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

abstract class DecoratedFile implements File
{
    private File $decorated;

    public function __construct(File $decorated)
    {
        $this->decorated = $decorated;
    }

    public function getId()
    {
        return $this->decorated->getId();
    }

    public function getOriginalFilename(): string
    {
        return $this->decorated->getOriginalFilename();
    }

    public function getFileSize(): int
    {
        return $this->decorated->getFileSize();
    }

    public function getMd5Hash(): string
    {
        return $this->decorated->getMd5Hash();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->decorated->getCreatedAt();
    }

    public function getMimeType(): string
    {
        return $this->decorated->getMimeType();
    }

    public function setOriginalFilename(string $filename): void
    {
        $this->decorated->setOriginalFilename($filename);
    }

    public function setFileSize(int $size): void
    {
        $this->decorated->setFileSize($size);
    }

    public function setMd5Hash(string $md5Hash): void
    {
        $this->decorated->setMd5Hash($md5Hash);
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->decorated->setCreatedAt($createdAt);
    }

    public function setMimeType(string $mimeType): void
    {
        $this->decorated->setMimeType($mimeType);
    }
}
