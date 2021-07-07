<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use ErrorException;
use function restore_error_handler;
use function set_error_handler;

class ErrorHandler
{
    /**
     * @param callable $callable
     * @return mixed
     * @throws ErrorException
     */
    public static function wrap(callable $callable)
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
            return $callable();
        } finally {
            restore_error_handler();
        }
    }
}
