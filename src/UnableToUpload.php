<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use RuntimeException;
use SplFileInfo;
use Throwable;

class UnableToUpload extends RuntimeException
{
    private SplFileInfo $relatedFile;

    public function __construct(SplFileInfo $file, Throwable $previous = null)
    {
        parent::__construct('Unable to upload file', 0, $previous);
        $this->relatedFile = $file;
    }

    public function getRelatedFile(): SplFileInfo
    {
        return $this->relatedFile;
    }
}
