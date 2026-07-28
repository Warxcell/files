<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use SplFileInfo;

/**
 * @template T of File
 * @template C
 * @implements ManagerInterface<T, C>
 * @implements PathResolver<T>
 */
final class PathResolverManager implements ManagerInterface, PathResolver
{
    /**
     * @param ManagerInterface<T, C> $manager
     * @param PathResolver<T> $pathResolver
     */
    public function __construct(
        private readonly ManagerInterface $manager,
        private readonly PathResolver $pathResolver
    ) {
    }

    #[\Override]
    public function upload(SplFileInfo $splFileInfo, mixed $context = null): File
    {
        return $this->manager->upload($splFileInfo, $context);
    }

    #[\Override]
    public function getPathname(File $file): string
    {
        return $this->manager->getPathname($file);
    }

    #[\Override]
    public function read(File $file): string
    {
        return $this->manager->read($file);
    }

    #[\Override]
    public function readStream(File $file)
    {
        return $this->manager->readStream($file);
    }

    #[\Override]
    public function write(MutableFile $file, SplFileInfo $splFileInfo): void
    {
        $this->manager->write($file, $splFileInfo);
    }

    #[\Override]
    public function moveFile(File $file): void
    {
        $this->manager->moveFile($file);
    }

    #[\Override]
    public function remove(File $file): void
    {
        $this->manager->remove($file);
    }

    #[\Override]
    public function getClass(): string
    {
        return $this->manager->getClass();
    }

    #[\Override]
    public function getPath(File $file): string
    {
        return $this->pathResolver->getPath($file);
    }

    #[\Override]
    public function clear(): void
    {
        $this->manager->clear();
    }
}
