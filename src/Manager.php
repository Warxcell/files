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
use Psr\EventDispatcher\EventDispatcherInterface;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
 * @implements ManagerInterface<T>
 */
final class Manager implements ManagerInterface
{
    private const CHUNK_SIZE = 1024 * 1024;
    /** @var class-string<T> */
    private string $class;
    private Storage $storage;
    /** @var NamingStrategy<T> */
    private NamingStrategy $namingStrategy;
    /** @var Repository<T>|null */
    private ?Repository $repository;
    /** @var FileMap<T, SplFileInfo> */
    private FileMap $uploadFileMap;
    private MimeTypeDetector $mimeTypeDetector;
    /** @var ModelFactory<T> */
    private ModelFactory $modelFactory;
    private ?EventDispatcherInterface $eventDispatcher;
    private string $temporaryDirectory;
    private string $hashingAlgorithm;

    /**
     * @param class-string<T> $class
     * @param NamingStrategy<T> $namingStrategy
     * @param Repository<T>|null $repository
     * @param ModelFactory<T>|null $modelFactory
     */
    public function __construct(
        string $class,
        Storage $storage,
        NamingStrategy $namingStrategy,
        ?Repository $repository = null,
        ?MimeTypeDetector $mimeTypeDetector = null,
        ?ModelFactory $modelFactory = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?string $temporaryDirectory = null,
        string $hashingAlgorithm = 'md5'
    ) {
        if (!in_array($hashingAlgorithm, hash_algos(), true)) {
            throw new InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $hashingAlgorithm));
        }

        $this->class = $class;
        $this->storage = $storage;
        $this->namingStrategy = $namingStrategy;
        $this->repository = $repository;
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->modelFactory = $modelFactory ?? new AbstractModelFactory($class);
        $this->eventDispatcher = $eventDispatcher;
        $this->temporaryDirectory = $temporaryDirectory ?? ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $this->hashingAlgorithm = $hashingAlgorithm;
        $this->clear();
    }

    public function upload(SplFileInfo $splFileInfo): File
    {
        try {
            $handledSplFile = $this->handleSplFile($splFileInfo);
        } catch (ErrorException $exception) {
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

        $fileSize = $handledSplFile->getSize();
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
            } catch (InvalidArgumentException $exception) {
                throw new UnableToUpload($handledSplFile, $exception);
            }
            $fileEntity = $this->modelFactory->create(
                $handledSplFile,
                $originalFilename,
                $fileSize,
                $hash,
                $mimeType
            );
            $this->uploadFileMap->put($fileEntity, $handledSplFile);

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PostUpload($this, $fileEntity));
            }
        }

        return $fileEntity;
    }

    public function moveFile(File $file): void
    {
        $splFileInfo = $this->uploadFileMap->get($file);

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

    public function getPathname(File $file): string
    {
        if ($this->uploadFileMap->has($file)) {
            return $this->uploadFileMap->get($file)->getPathname();
        } else {
            return $this->getPathnameFromNamingStrategy($file);
        }
    }

    public function remove(File $file): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(new PreRemove($this, $file));
        }

        $this->storage->remove($file, $this->getPathname($file));
    }

    public function read(File $file): string
    {
        $pathname = $this->getPathname($file);
        if ($this->uploadFileMap->has($file)) {
            try {
                return ErrorHandler::wrap(static fn (): string => file_get_contents($pathname));
            } catch (ErrorException $exception) {
                throw FileException::unableToRead($file, $exception);
            }
        } else {
            return $this->storage->read($file, $pathname);
        }
    }

    public function readStream(File $file)
    {
        $pathname = $this->getPathname($file);
        if ($this->uploadFileMap->has($file)) {
            try {
                return ErrorHandler::wrap(static fn () => fopen($pathname, 'rb'));
            } catch (ErrorException $exception) {
                throw FileException::unableToRead($file, $exception);
            }
        } else {
            return $this->storage->readStream($file, $pathname);
        }
    }

    public function write(MutableFile $file, SplFileInfo $splFileInfo): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch(new PreUpdate($this, $file));
        }

        try {
            $splFileInfo = $this->handleSplFile($splFileInfo);
        } catch (ErrorException $exception) {
            throw FileException::unableToWrite($file, $exception);
        }

        $pathname = $this->getPathname($file);
        if ($this->uploadFileMap->has($file)) {
            try {
                ErrorHandler::wrap(static fn (): bool => copy($splFileInfo->getRealPath(), $pathname));
            } catch (ErrorException $exception) {
                throw FileException::unableToWrite($file, $exception);
            }
            clearstatcache(true, $pathname);
        } else {
            try {
                $stream = ErrorHandler::wrap(static fn () => fopen($splFileInfo->getRealPath(), 'r'));
            } catch (ErrorException $exception) {
                throw FileException::unableToWrite($file, $exception);
            }
            $this->storage->write($file, $pathname, $stream);
            /** @psalm-suppress RedundantCondition */
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $file->setMimeType($this->getMimeTypeByFile($splFileInfo));
        $file->setSize($splFileInfo->getSize());
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

    public function getClass(): string
    {
        return $this->class;
    }

    public function clear(): void
    {
        $this->uploadFileMap = new FileMap();
    }

    /**
     * @throws ErrorException
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

        $tempFilename = ErrorHandler::wrap(fn (): string => tempnam($this->temporaryDirectory, 'file_manager'));
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
        return ErrorHandler::wrap(fn (): string => hash_file($this->hashingAlgorithm, $file->getRealPath()));
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getMimeTypeByFile(SplFileInfo $file): string
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromFile($file->getRealPath());
        if ($mimeType === null) {
            throw new InvalidArgumentException('Failed to detect mimeType for ' . $file->getRealPath());
        }

        return $mimeType;
    }

    private function getPathnameFromNamingStrategy(File $file): string
    {
        return NamingStrategyUtility::getPathnameFromStrategy($this->namingStrategy, $file);
    }
}
