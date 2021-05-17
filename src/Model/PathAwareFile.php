<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

interface PathAwareFile extends File
{
    public function getPathname(): string;

    public function setPathname(string $pathname): void;
}
