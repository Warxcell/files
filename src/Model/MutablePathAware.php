<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

interface MutablePathAware extends PathAwareFile
{
    public function setPathname(string $pathname): void;
}
