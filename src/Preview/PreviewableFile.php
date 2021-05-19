<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;

interface PreviewableFile extends File
{
    public function getPreview(): ?File;

    public function setPreview(?File $file): void;
}
