<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

class Dimension implements DimensionInterface
{
    private int $width;
    private int $height;

    public function __construct(int $width, int $height)
    {
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException(
                sprintf('Length of either side cannot be 0 or negative, current size is %sx%s', $width, $height)
            );
        }
        $this->width = $width;
        $this->height = $height;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }
}
