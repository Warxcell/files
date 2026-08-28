<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Utility;

use Arxy\FilesBundle\ErrorHandler;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\ReaderInterface;
use SplFileInfo;

use function fclose;
use function fopen;
use function stream_copy_to_stream;

/**
 * @template T of File
 */
class FileDownloader
{
    /**
     * @param ReaderInterface<T> $manager
     */
    public function __construct(
        private readonly ReaderInterface $manager
    ) {
    }

    /**
     * @param T $file
     * @throws \ErrorException
     */
    public function downloadAsSplFile(File $file): SplFileInfo
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('file-downloader', true);

        ErrorHandler::wrap(static fn (): bool => mkdir($tempDir));

        $tmpFile = $tempDir . DIRECTORY_SEPARATOR . $file->getOriginalFilename();

        $destinationStream = ErrorHandler::wrap(static fn () => fopen($tmpFile, 'w'));
        ErrorHandler::wrap(fn () => stream_copy_to_stream($this->manager->readStream($file), $destinationStream));

        fclose($destinationStream);

        return new SplFileInfo($tmpFile);
    }
}
