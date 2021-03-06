<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

interface File
{
    public function getOriginalFilename(): string;

    public function getSize(): int;

    public function getHash(): string;

    public function getCreatedAt(): DateTimeImmutable;

    public function getMimeType(): string;
}
