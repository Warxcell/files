<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Entity;

use Arxy\FilesBundle\Model\IdentifiableFile;
use DateTimeImmutable;

abstract class MutableFile extends File implements IdentifiableFile, \Arxy\FilesBundle\Model\MutableFile
{
    abstract public function getId();

    protected DateTimeImmutable $modifiedAt;

    public function __construct(string $originalFilename, int $size, string $hash, string $mimeType)
    {
        parent::__construct($originalFilename, $size, $hash, $mimeType);
        $this->modifiedAt = new DateTimeImmutable();
    }

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

    public function getModifiedAt(): DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(DateTimeImmutable $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
