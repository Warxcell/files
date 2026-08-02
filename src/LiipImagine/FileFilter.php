<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\LiipImagine;

use Arxy\FilesBundle\Model\DecoratedFile;
use Arxy\FilesBundle\Model\File;

/**
 * @extends DecoratedFile<File>
 */
class FileFilter extends DecoratedFile
{
    public function __construct(
        File $decorated,
        private readonly string $filter
    ) {
        parent::__construct($decorated);
    }

    public function getFilter(): string
    {
        return $this->filter;
    }
}
