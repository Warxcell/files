<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use InvalidArgumentException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use RuntimeException;
use SplFileInfo;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Manager implements ManagerInterface
{
    private string $class;
    private Repository $repository;
    private FilesystemOperator $filesystem;
    private NamingStrategy $namingStrategy;
    private FileMap $fileMap;
    private MimeTypeDetector $mimeTypeDetector;
    private ModelFactory $modelFactory;
    private const CHUNK_SIZE = 1024 * 1024;

    public function __construct(
        string $class,
        Repository $repository,
        FilesystemOperator $filesystem,
        NamingStrategy $namingStrategy,
        MimeTypeDetector $mimeTypeDetector = null,
        ModelFactory $modelFactory = null
    ) {
        $this->class = $class;
        $this->repository = $repository;
        $this->filesystem = $filesystem;
        $this->namingStrategy = $namingStrategy;
        $this->fileMap = new FileMap();
        $this->mimeTypeDetector = $mimeTypeDetector ?? new FinfoMimeTypeDetector();
        $this->modelFactory = $modelFactory ?? new AbstractModelFactory($class);
    }

    /**
     * @throws FilesystemException
     * @throws \Arxy\FilesBundle\InvalidArgumentException
     */
    public function moveFile(File $file): void
    {
        $splFileInfo = $this->fileMap->get($file);

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
        fclose($stream);
    }

    /**
     * @throws FilesystemException
     */
    public function remove(File $file): void
    {
        $this->filesystem->delete($this->getPathname($file));
    }

    private function getMimeTypeByFile(SplFileInfo $file): string
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromFile($file->getPathname());
        if ($mimeType === null) {
            throw new InvalidArgumentException('Failed to detect mimeType for '.$file->getPathname());
        }

        return $mimeType;
    }

    public function upload(SplFileInfo $file): File
    {
        if (!$file->getRealPath()) {
            $remoteFile = $file->openFile();

            $tempFilename = tempnam(sys_get_temp_dir(), 'file_manager');
            $file = new SplFileObject($tempFilename, 'r+');
            while ($content = $remoteFile->fread(self::CHUNK_SIZE)) {
                $file->fwrite($content);
            }

            $originalFilename = $remoteFile->getFilename();

            clearstatcache(true, $tempFilename);
        } else {
            if ($file instanceof UploadedFile) {
                $originalFilename = $file->getClientOriginalName();
            } else {
                $originalFilename = $file->getFilename();
            }
        }

        $fileSize = $file->getSize();
        $md5 = md5_file($file->getPathname());

        $fileEntity = $this->fileMap->findByHashAndSize($md5, $fileSize);

        if ($fileEntity === null) {
            $fileEntity = $this->repository->findByHashAndSize($md5, $fileSize);
        }
        if ($fileEntity === null) {
            $fileEntity = $this->modelFactory->create(
                $originalFilename,
                $fileSize,
                $md5,
                $this->getMimeTypeByFile($file)
            );
            $this->fileMap->put($fileEntity, $file);
        }

        return $fileEntity;
    }

    private function getPathnameFromNamingStrategy(File $file, NamingStrategy $namingStrategy = null): string
    {
        if ($namingStrategy === null) {
            $namingStrategy = $this->namingStrategy;
        }

        return ($namingStrategy->getDirectoryName($file) ?? "").$namingStrategy->getFileName($file);
    }

    public function getPathname(File $file): string
    {
        if ($this->fileMap->has($file)) {
            return $this->fileMap->get($file)->getPathname();
        } else {
            return $this->getPathnameFromNamingStrategy($file);
        }
    }

    /**
     * @throws FilesystemException
     */
    public function read(File $file): string
    {
        $pathname = $this->getPathname($file);
        if ($this->fileMap->has($file)) {
            return file_get_contents($pathname);
        } else {
            return $this->filesystem->read($pathname);
        }
    }

    /**
     * @return resource
     * @throws FilesystemException
     */
    public function readStream(File $file)
    {
        $pathname = $this->getPathname($file);
        if ($this->fileMap->has($file)) {
            return fopen($pathname, 'rb');
        } else {
            return $this->filesystem->readStream($pathname);
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
    private function md5Hash(File $file): string
    {
        $pathname = $this->getPathname($file);

        if ($this->fileMap->has($file)) {
            return md5_file($pathname);
        } else {
            return md5($this->filesystem->read($pathname));
        }
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
    public function refresh(File $file): void
    {
        $file->setMimeType($this->mimeType($file));
        $file->setFileSize($this->fileSize($file));
        $file->setMd5Hash($this->md5Hash($file));
    }

    /**
     * @throws FilesystemException
     */
    public function migrate(File $file, NamingStrategy $oldStrategy): bool
    {
        $oldName = $this->getPathnameFromNamingStrategy($file, $oldStrategy);

        if (!$this->filesystem->fileExists($oldName)) {
            return false;
        }

        $newName = $this->getPathnameFromNamingStrategy($file);

        $this->filesystem->move($oldName, $newName);

        return true;
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
