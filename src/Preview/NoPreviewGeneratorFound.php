<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Utility\FileUtility;
use RuntimeException;

class NoPreviewGeneratorFound extends RuntimeException
{
    public static function instance(File $file): self
    {
        return new self(
            sprintf(
                'No preview generator found for file %s',
                FileUtility::toString($file)
            )
        );
    }
}
