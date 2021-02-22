<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use League\Flysystem\FilesystemOperator;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Manager implements ManagerInterface
{
    private string $class;
    private Repository $repository;
    private FilesystemOperator $filesystem;
    private NamingStrategy $namingStrategy;
    private FileMap $fileMap;
    private MimeTypeDetector $mimeTypeDetector;

    public function __construct(
        string $class,
        Repository $repository,
        FilesystemOperator $filesystem,
        NamingStrategy $namingStrategy,
        MimeTypeDetector $mimeTypeDetector = null
    ) {
        $this->class = $class;
        $this->repository = $repository;
        $this->filesystem = $filesystem;
        $this->namingStrategy = $namingStrategy;
        $this->fileMap = new FileMap();
        $this->mimeTypeDetector = $mimeTypeDetector ?: new FinfoMimeTypeDetector();
    }

    public function moveFile(File $entity): void
    {
        $file = $this->fileMap->get($entity);

        $this->fileMap->remove($entity);

        $path = $this->getPathname($entity);

        $directory = $this->namingStrategy->getDirectoryName($entity);
        if ($directory !== null) {
            $this->filesystem->createDirectory($directory);
        }

        $stream = fopen($file->getPathname(), 'r');
        if (!$stream) {
            throw new \RuntimeException('Failed to open ', $file->getPathname());
        }
        $this->filesystem->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    public function remove(File $entity): void
    {
        $this->filesystem->delete($this->getPathname($entity));
    }

    private function getMimeTypeByFile(\SplFileInfo $file): string
    {
        $mimeType = $this->mimeTypeDetector->detectMimeTypeFromFile($file->getPathname());
        if ($mimeType === null) {
            throw new \InvalidArgumentException('Failed to detect mimeType for '.$file->getPathname());
        }

        return $mimeType;
    }

    public function upload(\SplFileInfo $file): File
    {
        if (!$file->getRealPath()) {
            $remoteFile = $file->openFile('r');

            $tempFilename = tempnam(sys_get_temp_dir(), 'file_manager');
            $file = new \SplFileObject($tempFilename, 'r+');
            $chunkSize = 1024 * 1024;
            while ($content = $remoteFile->fread($chunkSize)) {
                $file->fwrite($content);
            }
            $file->rewind();

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

        $fileEntity = $this->repository->findByHashAndSize($md5, $fileSize);

        if ($fileEntity === null) {
            $fileEntity = new $this->class();
            $fileEntity->setFileSize($fileSize);
            $fileEntity->setOriginalFilename($originalFilename);
            $fileEntity->setMd5Hash($md5);
            $fileEntity->setCreatedAt(new \DateTimeImmutable());
            $fileEntity->setMimeType($this->getMimeTypeByFile($file));

            $this->fileMap->put($fileEntity, $file);
        }

        return $fileEntity;
    }

    private function getPathnameFromNamingStrategy(File $file, NamingStrategy $namingStrategy = null): string
    {
        if ($namingStrategy === null) {
            $namingStrategy = $this->namingStrategy;
        }

        return $namingStrategy->getDirectoryName($file).$namingStrategy->getFileName($file);
    }

    public function getPathname(File $file): string
    {
        if ($this->fileMap->has($file)) {
            return $this->fileMap->get($file)->getPathname();
        } else {
            return $this->getPathnameFromNamingStrategy($file);
        }
    }

    public function read(File $file): string
    {
        $pathname = $this->getPathname($file);
        if ($this->fileMap->has($file)) {
            return file_get_contents($pathname);
        } else {
            return $this->filesystem->read($pathname);
        }
    }

    public function readStream(File $file)
    {
        $pathname = $this->getPathname($file);
        if ($this->fileMap->has($file)) {
            return fopen($pathname, 'rb');
        } else {
            return $this->filesystem->readStream($pathname);
        }
    }

    private function fileSize(File $file)
    {
        $pathname = $this->getPathname($file);

        if ($this->fileMap->has($file)) {
            return filesize($pathname);
        } else {
            return $this->filesystem->fileSize($pathname);
        }
    }

    private function md5Hash(File $file)
    {
        $pathname = $this->getPathname($file);

        if ($this->fileMap->has($file)) {
            return md5_file($pathname);
        } else {
            return md5($this->filesystem->read($pathname));
        }
    }

    private function mimeType(File $file)
    {
        if ($this->fileMap->has($file)) {
            return $this->getMimeTypeByFile($this->fileMap->get($file));
        } else {
            $pathname = $this->getPathname($file);

            return $this->filesystem->mimeType($pathname);
        }
    }

    public function refresh(File $file): void
    {
        $file->setFileSize($this->fileSize($file));
        $file->setMd5Hash($this->md5Hash($file));
        $file->setMimeType($this->mimeType($file));
    }

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
}
