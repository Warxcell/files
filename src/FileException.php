<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use RuntimeException;
use Throwable;

class FileException extends RuntimeException
{
    public static function unableToRead(File $file, Throwable $exception): self
    {
        return new self($file, 'Unable to read file', $exception);
    }

    public static function unableToWrite(File $file, Throwable $exception): self
    {
        return new self($file, 'Unable to write file', $exception);
    }

    public static function unableToMove(File $file, Throwable $exception): self
    {
        return new self($file, 'Unable to move file', $exception);
    }

    public static function unableToRemove(File $file, Throwable $exception): self
    {
        return new self($file, 'Unable to remove file', $exception);
    }

    private File $relatedFile;

    public function __construct(File $file, string $message, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->relatedFile = $file;
    }

    public function getRelatedFile(): File
    {
        return $this->relatedFile;
    }
}
