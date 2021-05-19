<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

interface DimensionInterface
{
    public function getWidth(): int;

    public function getHeight(): int;
}
