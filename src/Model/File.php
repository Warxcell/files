<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

interface File
{
    public function getId();

    public function setOriginalFilename(string $filename): void;

    public function getOriginalFilename(): string;

    public function setFileSize(int $size): void;

    public function getFileSize(): int;

    public function setMd5Hash(string $md5Hash): void;

    public function getMd5Hash(): string;

    public function setCreatedAt(DateTimeImmutable $createdAt): void;

    public function getCreatedAt(): DateTimeImmutable;

    public function setMimeType(string $mimeType): void;

    public function getMimeType(): string;
}
