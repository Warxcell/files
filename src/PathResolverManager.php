<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use SplFileInfo;

final class PathResolverManager implements ManagerInterface, PathResolver
{
    private ManagerInterface $manager;
    private PathResolver $pathResolver;

    public function __construct(ManagerInterface $manager, PathResolver $pathResolver)
    {
        $this->manager = $manager;
        $this->pathResolver = $pathResolver;
    }

    public function upload(SplFileInfo $file): File
    {
        return $this->manager->upload($file);
    }

    public function getPathname(File $file): string
    {
        return $this->manager->getPathname($file);
    }

    public function read(File $file): string
    {
        return $this->manager->read($file);
    }

    public function readStream(File $file)
    {
        return $this->manager->readStream($file);
    }

    public function write(MutableFile $file, SplFileInfo $fileInfo): void
    {
        $this->manager->write($file, $fileInfo);
    }
    
    public function moveFile(File $file): void
    {
        $this->manager->moveFile($file);
    }

    public function remove(File $file): void
    {
        $this->manager->remove($file);
    }

    public function getClass(): string
    {
        return $this->manager->getClass();
    }

    public function getPath(File $file): string
    {
        return $this->pathResolver->getPath($file);
    }

    public function clear(): void
    {
        $this->manager->clear();
    }
}
