<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

interface File
{
    public function getId();

    public function getOriginalFilename(): string;

    public function getFileSize(): int;

    public function getMd5Hash(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getMimeType(): string;
}
