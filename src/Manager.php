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
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;
use function clearstatcache;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function fopen;
use function hash;
use function hash_algos;
use function hash_file;
use function in_array;
use function ini_get;
use function is_resource;
use function is_subclass_of;
use function stream_copy_to_stream;
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
    private FilesystemOperator $filesystem;
    /** @var NamingStrategy<T> */
    private NamingStrategy $namingStrategy;
    /** @var Repository<T>|null */
    private ?Repository $repository;
    /** @var FileMap<T> */
    private FileMap $fileMap;
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
        if (!is_subclass_of($class, File::class)) {
            throw new InvalidArgumentException('$class must be subclass of '.File::class);
        }

        $this->class = $class;
        $this->filesystem = $filesystem;
        $this->namingStrategy = $namingStrategy;
        $this->repository = $repository;
        $this->fileMap = new FileMap();
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->modelFactory = $modelFactory ?? new AbstractModelFactory($class);
        $this->eventDispatcher = $eventDispatcher;
        $this->temporaryDirectory = $temporaryDirectory ?? ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
        $this->hashingAlgorithm = $hashingAlgorithm;
    }

    public function upload(SplFileInfo $file): File
    {
        try {
            if (!$file->getRealPath()) {
                if ($file instanceof SplFileObject) {
                    $remoteFile = $file;
                    $remoteFile->rewind();
                } else {
                    $remoteFile = $file->openFile();
                }

                $tempFilename = tempnam($this->temporaryDirectory, 'file_manager');
                $file = new SplFileObject($tempFilename, 'r+');
                while ($content = $remoteFile->fread(self::CHUNK_SIZE)) {
                    $file->fwrite($content);
                }

                $originalFilename = $remoteFile->getFilename();
                unset($remoteFile);

                clearstatcache(true, $tempFilename);
            } else {
                if ($file instanceof UploadedFile) {
                    $originalFilename = $file->getClientOriginalName();
                } else {
                    $originalFilename = $file->getFilename();
                }
            }

            $fileSize = $file->getSize();
            $hash = hash_file($this->hashingAlgorithm, $file->getPathname());

            $fileEntity = null;
            if ($this->repository !== null) {
                $fileEntity = $this->fileMap->findByHashAndSize($hash, $fileSize);

                if ($fileEntity === null) {
                    $fileEntity = $this->repository->findByHashAndSize($hash, $fileSize);
                }
            }
            if ($fileEntity === null) {
                $fileEntity = $this->modelFactory->create(
                    $file,
                    $originalFilename,
                    $fileSize,
                    $hash,
                    $this->getMimeTypeByFile($file)
                );
                $this->fileMap->put($fileEntity, $file);

                if ($this->eventDispatcher !== null) {
                    $this->eventDispatcher->dispatch(new PostUpload($this, $fileEntity));
                }
            }

            return $fileEntity;
        } catch (Throwable $exception) {
            throw new UnableToUpload($file, $exception);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getMimeTypeByFile(SplFileInfo $file): string
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromFile($file->getPathname());
        if ($mimeType === null) {
            throw new InvalidArgumentException('Failed to detect mimeType for '.$file->getPathname());
        }

        return $mimeType;
    }

    public function moveFile(File $file): void
    {
        try {
            $splFileInfo = $this->fileMap->get($file);

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PreMove($this, $file));
            }

            $this->fileMap->remove($file);

            $path = $this->getPathname($file);

            $directory = $this->namingStrategy->getDirectoryName($file);
            if ($directory !== null) {
                $this->filesystem->createDirectory($directory);
            }

            $stream = @fopen($splFileInfo->getPathname(), 'r');
            if (!$stream) {
                throw new RuntimeException('Failed to open '.$splFileInfo->getPathname());
            }
            $this->filesystem->writeStream($path, $stream);

            /** @psalm-suppress RedundantCondition */
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PostMove($this, $file));
            }
        } catch (Throwable $throwable) {
            throw FileException::unableToMove($file, $throwable);
        }
    }

    public function getPathname(File $file): string
    {
        if ($this->fileMap->has($file)) {
            return $this->fileMap->get($file)->getPathname();
        } else {
            return $this->getPathnameFromNamingStrategy($file);
        }
    }

    private function getPathnameFromNamingStrategy(File $file): string
    {
        return NamingStrategyUtility::getPathnameFromStrategy($this->namingStrategy, $file);
    }

    public function remove(File $file): void
    {
        try {
            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PreRemove($this, $file));
            }

            $this->filesystem->delete($this->getPathname($file));
        } catch (Throwable $exception) {
            throw FileException::unableToRemove($file, $exception);
        }
    }

    public function read(File $file): string
    {
        try {
            $pathname = $this->getPathname($file);
            if ($this->fileMap->has($file)) {
                return file_get_contents($pathname);
            } else {
                return $this->filesystem->read($pathname);
            }
        } catch (Throwable $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    public function readStream(File $file)
    {
        try {
            $pathname = $this->getPathname($file);
            if ($this->fileMap->has($file)) {
                return fopen($pathname, 'rb');
            } else {
                return $this->filesystem->readStream($pathname);
            }
        } catch (Throwable $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    public function write(MutableFile $file, string $contents): void
    {
        try {
            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PreUpdate($this, $file));
            }

            $pathname = $this->getPathname($file);
            if ($this->fileMap->has($file)) {
                file_put_contents($pathname, $contents);
                clearstatcache(true, $pathname);
            } else {
                $this->filesystem->write($pathname, $contents);
            }
            $this->refresh($file);

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PostUpdate($this, $file));
            }
        } catch (Throwable $exception) {
            throw FileException::unableToWrite($file, $exception);
        }
    }

    /**
     * @throws FilesystemException
     */
    private function refresh(MutableFile $file): void
    {
        $file->setMimeType($this->mimeType($file));
        $file->setSize($this->fileSize($file));
        $file->setHash($this->hash($file));
        $file->setModifiedAt(new DateTimeImmutable());
    }

    /**
     * @throws FilesystemException
     */
    private function mimeType(File $file): string
    {
        if ($this->fileMap->has($file)) {
            return $this->getMimeTypeByFile($this->fileMap->get($file));
        } else {
            $pathname = $this->getPathname($file);

            return $this->filesystem->mimeType($pathname);
        }
    }

    /**
     * @throws FilesystemException
     */
    private function fileSize(File $file): int
    {
        $pathname = $this->getPathname($file);

        if ($this->fileMap->has($file)) {
            return filesize($pathname);
        } else {
            return $this->filesystem->fileSize($pathname);
        }
    }

    /**
     * @throws FilesystemException
     */
    private function hash(File $file): string
    {
        $pathname = $this->getPathname($file);

        if ($this->fileMap->has($file)) {
            return hash_file($this->hashingAlgorithm, $pathname);
        } else {
            return hash($this->hashingAlgorithm, $this->filesystem->read($pathname));
        }
    }

    public function writeStream(MutableFile $file, $resource): void
    {
        try {
            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PreUpdate($this, $file));
            }

            $pathname = $this->getPathname($file);
            if ($this->fileMap->has($file)) {
                $stream = fopen($pathname, 'w+b');
                stream_copy_to_stream($resource, $stream);
                fclose($stream);
                clearstatcache(true, $pathname);
            } else {
                $this->filesystem->writeStream($pathname, $resource);
            }
            $this->refresh($file);

            if ($this->eventDispatcher !== null) {
                $this->eventDispatcher->dispatch(new PostUpdate($this, $file));
            }
        } catch (Throwable $exception) {
            throw FileException::unableToWrite($file, $exception);
        }
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function clear(): void
    {
        $this->fileMap = new FileMap();
    }
}
