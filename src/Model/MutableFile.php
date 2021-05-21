<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

interface MutableFile extends File
{
    public function setOriginalFilename(string $originalFilename): void;

    public function setFileSize(int $fileSize): void;

    public function setMd5Hash(string $md5Hash): void;

    public function setCreatedAt(DateTimeImmutable $createdAt): void;

    public function setMimeType(string $mimeType): void;
}
