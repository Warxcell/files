<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Utility;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;

/**
 * @internal
 */
class NamingStrategyUtility
{
    public static function getPathnameFromStrategy(NamingStrategy $namingStrategy, File $file): string
    {
        return ($namingStrategy->getDirectoryName($file) ?? "").$namingStrategy->getFileName($file);
    }
}
