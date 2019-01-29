<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Doctrine\Common\Persistence\ManagerRegistry;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Manager
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var NamingStrategy
     */
    private $namingStrategy;

    /**
     * @var FileMap
     */
    private $fileMap;

    public function __construct(
        string $class,
        ManagerRegistry $doctrine,
        Filesystem $filesystem,
        NamingStrategy $namingStrategy
    ) {
        $this->class = $class;
        $this->doctrine = $doctrine;
        $this->filesystem = $filesystem;
        $this->namingStrategy = $namingStrategy;
        $this->fileMap = new FileMap();
    }

    public function moveFile(File $entity): void
    {
        $file = $this->fileMap->get($entity);

        $this->fileMap->remove($entity);

        $path = $this->getPathname($entity);

        $this->filesystem->createDir($this->namingStrategy->getDirectoryName($entity));

        $stream = fopen($file->getPathname(), 'r+');
        $this->filesystem->writeStream($path, $stream);
        fclose($stream);
    }

    public function remove(File $entity): void
    {
        $this->filesystem->delete($this->getPathname($entity));
    }

    public function getMimeTypeByFile(\SplFileInfo $file): string
    {
        $finfo = new \finfo();

        return $finfo->file($file->getPathname(), FILEINFO_MIME_TYPE);
    }

    public function upload(\SplFileInfo $file): File
    {
        $fileSize = $file->getSize();
        $md5 = md5_file($file->getPathname());

        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        /** @var File $fileEntity */
        $fileEntity = $this->doctrine->getRepository($this->class)->findOneBy(
            ['md5Hash' => $md5, 'fileSize' => $fileSize]
        );

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

    private function getPathnameFromNamingStrategy(File $file): string
    {
        return $this->namingStrategy->getDirectoryName($file).$this->namingStrategy->getFileName($file);
    }

    public function getPathname(File $file): string
    {
        if ($this->fileMap->has($file)) {
            return $this->fileMap->get($file)->getPathname();
        } else {
            return $this->getPathnameFromNamingStrategy($file);
        }
    }

    public function read(File $file)
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
}
