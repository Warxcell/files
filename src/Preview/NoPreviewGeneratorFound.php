<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;
use RuntimeException;
use function method_exists;

class NoPreviewGeneratorFound extends RuntimeException
{
    public static function instance(File $file): self
    {
        return new self(
            'No preview generator found for file '.
            (method_exists($file, '__toString') ? (string)$file : (string)spl_object_id($file))
        );
    }
}
