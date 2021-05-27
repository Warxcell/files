<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

interface MutableFile extends File
{
    public function setOriginalFilename(string $originalFilename): void;

    public function setSize(int $size): void;

    public function setHash(string $hash): void;

    public function setCreatedAt(DateTimeImmutable $createdAt): void;

    public function setMimeType(string $mimeType): void;
}
