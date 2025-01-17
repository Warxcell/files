<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use ErrorException;

use function restore_error_handler;
use function set_error_handler;

class ErrorHandler
{
    /**
     * @template T
     * @param callable(): (T|false) $callable
     * @return T
     * @throws ErrorException
     */
    public static function wrap(callable $callable): mixed
    {
        set_error_handler(
            static function (int $errno, string $message, string $file, int $line): bool {
                throw new ErrorException(
                    $message,
                    0,
                    $errno,
                    $file,
                    $line
                );
            }
        );
        try {
            $value = $callable();
            if ($value === false) {
                throw new ErrorException('Unknown error');
            }

            return $value;
        } finally {
            restore_error_handler();
        }
    }
}
