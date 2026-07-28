<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Event\PostMove;
use Arxy\FilesBundle\Event\PostUpdate;
use Arxy\FilesBundle\Event\PostUpload;
use Arxy\FilesBundle\Event\PreMove;
use Arxy\FilesBundle\Event\PreRemove;
use Arxy\FilesBundle\Event\PreUpdate;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use Arxy\FilesBundle\Utility\NamingStrategyUtility;
use DateTimeImmutable;
use ErrorException;
use InvalidArgumentException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use OutOfBoundsException;
use Psr\EventDispatcher\EventDispatcherInterface;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

use function clearstatcache;
use function copy;
use function fclose;
use function file_get_contents;
use function fopen;
use function hash_algos;
use function hash_file;
use function in_array;
use function ini_get;
use function is_resource;
use function sys_get_temp_dir;
use function tempnam;

/**
 * @template T of File
 * @template C
 * @implements ManagerInterface<T, C>
 */
final class Manager implements ManagerInterface
{
    private const CHUNK_SIZE = 1024 * 1024;
    /** @var FileMap<T> */
    private FileMap $uploadFileMap;
    private MimeTypeDetector $mimeTypeDetector;
    /** @var ModelFactory<T, C> */
    private ModelFactory $modelFactory;
    private string $temporaryDirectory;

    /**
     * @param class-string<T> $class
     * @param Storage<T> $storage
     * @param NamingStrategy<T> $namingStrategy
     * @param Repository<T>|null $repository
     * @param ModelFactory<T, C>|null $modelFactory
     * @throws InvalidArgumentException
     */
    public function __construct(
        private string $class,
        private Storage $storage,
        private NamingStrategy $namingStrategy,
        private ?Repository $repository = null,
        ?MimeTypeDetector $mimeTypeDetector = null,
        ?ModelFactory $modelFactory = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
        ?string $temporaryDirectory = null,
        private string $hashingAlgorithm = 'md5'
    ) {
        if (!in_array($hashingAlgorithm, hash_algos(), true)) {
            throw new InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $hashingAlgorithm));
        }

        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->modelFactory = $modelFactory ?? new AbstractModelFactory($class);
        $this->temporaryDirectory = $temporaryDirectory ?? ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $this->clear();
    }

    #[\Override]
    public function upload(SplFileInfo $splFileInfo, mixed $context = null): File
    {
        try {
            $handledSplFile = $this->handleSplFile($splFileInfo);
        } catch (Throwable $exception) {
            throw new UnableToUpload($splFileInfo, $exception);
        }

        if ($handledSplFile !== $splFileInfo) {
            $originalFilename = $splFileInfo->getFilename();
        } else {
            if ($handledSplFile instanceof UploadedFile) {
                $originalFilename = $handledSplFile->getClientOriginalName();
            } else {
                $originalFilename = $handledSplFile->getFilename();
            }
        }

        try {
            $fileSize = ErrorHandler::wrap(static fn () => $handledSplFile->getSize());
        } catch (Throwable $exception) {
            throw new UnableToUpload($handledSplFile, $exception);
        }
        try {
            $hash = $this->hashFile($handledSplFile);
        } catch (ErrorException $exception) {
            throw new UnableToUpload($handledSplFile, $exception);
        }

        $fileEntity = null;
        if ($this->repository !== null) {
            $fileEntity = $this->uploadFileMap->findByHashAndSize($hash, $fileSize);

            if ($fileEntity === null) {
                $fileEntity = $this->repository->findByHashAndSize($hash, $fileSize);
            }
        }
        if ($fileEntity === null) {
            try {
                $mimeType = $this->getMimeTypeByFile($handledSplFile);
            } catch (InvalidArgumentException|ErrorException $exception) {
                throw new UnableToUpload($handledSplFile, $exception);
            }
            $fileEntity = $this->modelFactory->create(
                $handledSplFile,
                $originalFilename,
                $fileSize,
                $hash,
                $mimeType,
                $context
            );
            $this->uploadFileMap->put($fileEntity, $handledSplFile);

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PostUpload($this, $fileEntity));
            }
        }

        return $fileEntity;
    }

    #[\Override]
    public function moveFile(File $file): void
    {
        $splFileInfo = $this->uploadFileMap->get($file);
        if ($splFileInfo === null) {
            throw FileException::unableToMove($file, new OutOfBoundsException('File is not uploaded.'));
        }

        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(new PreMove($this, $file));
        }

        $this->uploadFileMap->remove($file);

        $path = $this->getPathname($file);

        try {
            $stream = ErrorHandler::wrap(static fn () => fopen($splFileInfo->getPathname(), 'r'));
        } catch (ErrorException $exception) {
            throw FileException::unableToMove($file, $exception);
        }
        $this->storage->write($file, $path, $stream);

        /** @psalm-suppress RedundantCondition */
        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(new PostMove($this, $file));
        }
    }

    #[\Override]
    public function getPathname(File $file): string
    {
        $tmpFile = $this->uploadFileMap->get($file);
        if ($tmpFile !== null) {
            return $tmpFile->getPathname();
        } else {
            return $this->getPathnameFromNamingStrategy($file);
        }
    }

    #[\Override]
    public function remove(File $file): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(new PreRemove($this, $file));
        }

        $this->storage->remove($file, $this->getPathname($file));
    }

    #[\Override]
    public function read(File $file): string
    {
        $tmpFile = $this->uploadFileMap->get($file);
        if ($tmpFile !== null) {
            try {
                return ErrorHandler::wrap(static fn () => file_get_contents($tmpFile->getPathname()));
            } catch (ErrorException $exception) {
                throw FileException::unableToRead($file, $exception);
            }
        } else {
            return $this->storage->read($file, $this->getPathnameFromNamingStrategy($file));
        }
    }

    #[\Override]
    public function readStream(File $file)
    {
        $tmpFile = $this->uploadFileMap->get($file);
        if ($tmpFile !== null) {
            try {
                return ErrorHandler::wrap(static fn () => fopen($tmpFile->getPathname(), 'rb'));
            } catch (ErrorException $exception) {
                throw FileException::unableToRead($file, $exception);
            }
        } else {
            return $this->storage->readStream($file, $this->getPathnameFromNamingStrategy($file));
        }
    }

    #[\Override]
    public function write(MutableFile $file, SplFileInfo $splFileInfo): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(new PreUpdate($this, $file));
        }

        try {
            $splFileInfo = $this->handleSplFile($splFileInfo);
        } catch (Throwable $exception) {
            throw FileException::unableToWrite($file, $exception);
        }

        $tmpFile = $this->uploadFileMap->get($file);
        if ($tmpFile !== null) {
            try {
                ErrorHandler::wrap(static fn () => copy(
                    ErrorHandler::wrap(static fn () => $splFileInfo->getRealPath()),
                    $tmpFile->getPathname()
                ));
            } catch (ErrorException $exception) {
                throw FileException::unableToWrite($file, $exception);
            }
            clearstatcache(true, $tmpFile->getPathname());
        } else {
            try {
                $stream = ErrorHandler::wrap(static fn () => fopen(
                    ErrorHandler::wrap(static fn () => $splFileInfo->getRealPath()),
                    'r'
                ));
            } catch (ErrorException $exception) {
                throw FileException::unableToWrite($file, $exception);
            }
            $this->storage->write($file, $this->getPathnameFromNamingStrategy($file), $stream);
            /** @psalm-suppress RedundantCondition */
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        try {
            $file->setMimeType($this->getMimeTypeByFile($splFileInfo));
            $file->setSize(ErrorHandler::wrap(static fn () => $splFileInfo->getSize()));
        } catch (Throwable $exception) {
            throw FileException::unableToWrite($file, $exception);
        }
        try {
            $file->setHash($this->hashFile($splFileInfo));
        } catch (ErrorException $exception) {
            throw FileException::unableToWrite($file, $exception);
        }
        $file->setModifiedAt(new DateTimeImmutable());

        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(new PostUpdate($this, $file));
        }
    }

    #[\Override]
    public function getClass(): string
    {
        return $this->class;
    }

    #[\Override]
    public function clear(): void
    {
        $this->uploadFileMap = new FileMap();
    }

    /**
     * @throws Throwable
     */
    private function handleSplFile(SplFileInfo $file): SplFileInfo
    {
        $isRemote = $file->getRealPath() === false;

        if (!$isRemote) {
            return $file;
        }

        if ($file instanceof SplFileObject) {
            $remoteFile = $file;
            $remoteFile->rewind();
        } else {
            $remoteFile = $file->openFile();
        }

        $tempFilename = ErrorHandler::wrap(fn () => tempnam($this->temporaryDirectory, 'file_manager'));
        $file = new SplFileObject($tempFilename, 'r+');
        while ($content = $remoteFile->fread(self::CHUNK_SIZE)) {
            $file->fwrite($content);
        }
        unset($remoteFile);
        clearstatcache(true, $tempFilename);

        return $file;
    }

    /**
     * @throws ErrorException
     */
    private function hashFile(SplFileInfo $file): string
    {
        return ErrorHandler::wrap(
            fn () => hash_file(
                $this->hashingAlgorithm,
                ErrorHandler::wrap(static fn () => $file->getRealPath())
            )
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws ErrorException
     */
    private function getMimeTypeByFile(SplFileInfo $file): string
    {
        $realPath = ErrorHandler::wrap(static fn () => $file->getRealPath());
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromFile($realPath);
        if ($mimeType === null) {
            throw new InvalidArgumentException('Failed to detect mimeType for "' . $realPath . "'");
        }

        return $mimeType;
    }

    /**
     * @param T $file
     * @return string
     */
    private function getPathnameFromNamingStrategy(File $file): string
    {
        return NamingStrategyUtility::getPathnameFromStrategy($this->namingStrategy, $file);
    }
}
