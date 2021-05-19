<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;
use RuntimeException;

class NoPreviewGeneratorFound extends RuntimeException
{
    public static function instance(File $file): self
    {
        return new self(
            'No preview generator found for file '.
            (method_exists($file, '__toString') ? (string)$file : spl_object_id($file))
        );
    }
}
