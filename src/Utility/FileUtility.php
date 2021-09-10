<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Utility;

use Arxy\FilesBundle\Model\File;

use function method_exists;

class FileUtility
{
    public static function toString(File $file): string
    {
        return (string)(method_exists($file, '__toString') ? $file : spl_object_id($file));
    }
}
