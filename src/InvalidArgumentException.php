<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

class InvalidArgumentException extends \InvalidArgumentException
{
    public const INVALID_TYPE = 0;
    public const FILE_NOT_EXISTS_IN_MAP = 1;
    public const INVALID_CLASS = 2;

    public static function invalidClass(string $class, string $expected): self
    {
        return new self(
            sprintf('Expected class of type "%s", "%s" given', $expected, $class),
            self::INVALID_CLASS
        );
    }

    public static function invalidType(object $value, string $expectedType): self
    {
        return new self(
            sprintf('Expected argument of type "%s", "%s" given', $expectedType, get_debug_type($value)),
            self::INVALID_TYPE
        );
    }

    public static function fileNotExistsInMap(File $file): self
    {
        return new self(
            sprintf(
                'File %s not found in map',
                method_exists($file, '__toString') ? (string)$file : spl_object_id($file)
            ),
            self::FILE_NOT_EXISTS_IN_MAP
        );
    }
}
