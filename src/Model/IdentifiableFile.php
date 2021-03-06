<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

interface IdentifiableFile extends File
{
    /**
     * @return string|int
     */
    public function getId();
}
