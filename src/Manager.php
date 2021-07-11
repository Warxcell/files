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
use Exception;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
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
 * @template T of \Arxy\FilesBundle\Model\File
 * @implements ManagerInterface<T>
 */
final class Manager implements ManagerInterface
{
    private const CHUNK_SIZE = 1024 * 1024;
    /** @var class-string<T> */
    private string $class;
    private FilesystemOperator $filesystem;
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
     * @param FilesystemOperator $filesystem
     * @param NamingStrategy<T> $namingStrategy
     * @param Repository<T>|null $repository
     * @param MimeTypeDetector|null $mimeTypeDetector
     * @param ModelFactory<T>|null $modelFactory
     * @param EventDispatcherInterface|null $eventDispatcher
     * @param string|null $temporaryDirectory
     * @param string $hashingAlgorithm
     */
    public function __construct(
        string $class,
        FilesystemOperator $filesystem,
        NamingStrategy $namingStrategy,
        Repository $repository = null,
        MimeTypeDetector $mimeTypeDetector = null,
        ModelFactory $modelFactory = null,
        EventDispatcherInterface $eventDispatcher = null,
        string $temporaryDirectory = null,
        string $hashingAlgorithm = 'md5'
    ) {
        if (!in_array($hashingAlgorithm, hash_algos(), true)) {
            throw new InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $hashingAlgorithm));
        }

        $this->class = $class;
        $this->filesystem = $filesystem;
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
        $handledSplFile = $this->handleSplFile($splFileInfo);

        try {
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
            $hash = $this->hashFile($handledSplFile);

            $fileEntity = null;
            if ($this->repository !== null) {
                $fileEntity = $this->uploadFileMap->findByHashAndSize($hash, $fileSize);

                if ($fileEntity === null) {
                    $fileEntity = $this->repository->findByHashAndSize($hash, $fileSize);
                }
            }
            if ($fileEntity === null) {
                $fileEntity = $this->modelFactory->create(
                    $handledSplFile,
                    $originalFilename,
                    $fileSize,
                    $hash,
                    $this->getMimeTypeByFile($handledSplFile)
                );
                $this->uploadFileMap->put($fileEntity, $handledSplFile);

                if ($this->eventDispatcher !== null) {
                    $this->eventDispatcher->dispatch(new PostUpload($this, $fileEntity));
                }
            }

            return $fileEntity;
        } catch (Exception $exception) {
            throw new UnableToUpload($handledSplFile, $exception);
        }
    }

    public function moveFile(File $file): void
    {
        try {
            $splFileInfo = $this->uploadFileMap->get($file);

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PreMove($this, $file));
            }

            $this->uploadFileMap->remove($file);

            $path = $this->getPathname($file);

            $directory = $this->namingStrategy->getDirectoryName($file);
            if ($directory !== null) {
                $this->filesystem->createDirectory($directory);
            }

            $stream = ErrorHandler::wrap(static fn () => fopen($splFileInfo->getPathname(), 'r'));

            $this->filesystem->writeStream($path, $stream);

            /** @psalm-suppress RedundantCondition */
            if (is_resource($stream)) {
                ErrorHandler::wrap(static fn () => fclose($stream));
            }

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PostMove($this, $file));
            }
        } catch (Exception $throwable) {
            throw FileException::unableToMove($file, $throwable);
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
        try {
            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PreRemove($this, $file));
            }

            $this->filesystem->delete($this->getPathname($file));
        } catch (Exception $exception) {
            throw FileException::unableToRemove($file, $exception);
        }
    }

    public function read(File $file): string
    {
        try {
            $pathname = $this->getPathname($file);
            if ($this->uploadFileMap->has($file)) {
                return ErrorHandler::wrap(static fn () => file_get_contents($pathname));
            } else {
                return $this->filesystem->read($pathname);
            }
        } catch (Exception $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    public function readStream(File $file)
    {
        try {
            $pathname = $this->getPathname($file);
            if ($this->uploadFileMap->has($file)) {
                return ErrorHandler::wrap(static fn () => fopen($pathname, 'rb'));
            } else {
                return $this->filesystem->readStream($pathname);
            }
        } catch (Exception $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    public function write(MutableFile $file, SplFileInfo $splFileInfo): void
    {
        try {
            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PreUpdate($this, $file));
            }

            $splFileInfo = $this->handleSplFile($splFileInfo);

            $pathname = $this->getPathname($file);
            if ($this->uploadFileMap->has($file)) {
                ErrorHandler::wrap(static fn () => copy($splFileInfo->getRealPath(), $pathname));
                clearstatcache(true, $pathname);
            } else {
                $stream = ErrorHandler::wrap(static fn () => fopen($splFileInfo->getRealPath(), 'r'));
                $this->filesystem->writeStream($pathname, $stream);
                /** @psalm-suppress RedundantCondition */
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $file->setMimeType($this->getMimeTypeByFile($splFileInfo));
            $file->setSize($splFileInfo->getSize());
            $file->setHash($this->hashFile($splFileInfo));
            $file->setModifiedAt(new DateTimeImmutable());

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PostUpdate($this, $file));
            }
        } catch (Exception $exception) {
            throw FileException::unableToWrite($file, $exception);
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

    private function hashFile(SplFileInfo $file): string
    {
        return ErrorHandler::wrap(fn () => hash_file($this->hashingAlgorithm, $file->getRealPath()));
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getMimeTypeByFile(SplFileInfo $file): string
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromFile($file->getRealPath());
        if ($mimeType === null) {
            throw new InvalidArgumentException('Failed to detect mimeType for '.$file->getRealPath());
        }

        return $mimeType;
    }

    private function getPathnameFromNamingStrategy(File $file): string
    {
        return NamingStrategyUtility::getPathnameFromStrategy($this->namingStrategy, $file);
    }
}
