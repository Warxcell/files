<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;

class GeneratePreviewMessage
{
    /**
     * @param PreviewableFile<File> $file
     */
    public function __construct(
        private readonly PreviewableFile $file
    ) {
    }

    /**
     * @return PreviewableFile<File>
     */
    public function getFile(): PreviewableFile
    {
        return $this->file;
    }
}
