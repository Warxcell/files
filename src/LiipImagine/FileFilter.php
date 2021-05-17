<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\LiipImagine;

use Arxy\FilesBundle\Model\DecoratedFile;
use Arxy\FilesBundle\Model\File;

class FileFilter extends DecoratedFile
{
    private string $filter;

    public function __construct(File $decorated, string $filter)
    {
        parent::__construct($decorated);
        $this->filter = $filter;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }
}
