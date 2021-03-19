<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

final class PathResolverManager implements ManagerInterface, PathResolver
{
    private ManagerInterface $manager;
    private PathResolver $pathResolver;

    public function __construct(ManagerInterface $manager, PathResolver $pathResolver)
    {
        $this->manager = $manager;
        $this->pathResolver = $pathResolver;
    }

    public function upload(\SplFileInfo $file): File
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

    public function refresh(File $file): void
    {
        $this->manager->refresh($file);
    }

    public function migrate(File $file, NamingStrategy $oldStrategy): bool
    {
        return $this->manager->migrate($file, $oldStrategy);
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
