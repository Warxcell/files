<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

class GeneratePreviewMessage
{
    private PreviewableFile $file;

    public function __construct(PreviewableFile $file)
    {
        $this->file = $file;
    }

    public function getFile(): PreviewableFile
    {
        return $this->file;
    }
}