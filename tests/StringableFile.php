<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

class StringableFile extends File
{
    public function __toString()
    {
        return (string)$this->getId();
    }
}
