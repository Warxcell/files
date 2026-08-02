<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Entity;

use DateTimeImmutable;
use Symfony\Component\Clock\DatePoint;

abstract class MutableFile extends File implements \Arxy\FilesBundle\Model\MutableFile
{
    protected DateTimeImmutable $modifiedAt;

    public function __construct(string $originalFilename, int $size, string $hash, string $mimeType)
    {
        parent::__construct($originalFilename, $size, $hash, $mimeType);
        $this->modifiedAt = new DatePoint();
    }

    #[\Override]
    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    #[\Override]
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    #[\Override]
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    #[\Override]
    public function getModifiedAt(): DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    #[\Override]
    public function setModifiedAt(DateTimeImmutable $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }

    #[\Override]
    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
