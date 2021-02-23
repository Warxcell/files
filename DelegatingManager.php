<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

final class DelegatingManager implements ManagerInterface
{
    /** @var ManagerInterface[] */
    private array $managers;
    private ManagerInterface $manager;

    public function __construct(array $managers, ManagerInterface $manager = null)
    {
        $this->managers = $managers;

        if ($manager !== null) {
            $this->manager = $manager;
        } else {
            $this->manager = $managers[0];
        }
    }

    private function getManagerFor(File $file): ManagerInterface
    {
        foreach ($this->managers as $manager) {
            $class = $manager->getClass();
            if ($file instanceof $class) {
                return $manager;
            }
        }
        throw new \LogicException('No manager for '.get_class($file));
    }

    public function upload(\SplFileInfo $file): File
    {
        return $this->manager->upload($file);
    }

    public function getPathname(File $file): string
    {
        return $this->getManagerFor($file)->getPathname($file);
    }

    public function read(File $file): string
    {
        return $this->getManagerFor($file)->read($file);
    }

    public function readStream(File $file)
    {
        return $this->getManagerFor($file)->readStream($file);
    }

    public function refresh(File $file): void
    {
        $this->getManagerFor($file)->refresh($file);
    }

    public function migrate(File $file, NamingStrategy $oldStrategy): bool
    {
        return $this->getManagerFor($file)->migrate($file, $oldStrategy);
    }

    public function moveFile(File $file): void
    {
        $this->getManagerFor($file)->moveFile($file);
    }

    public function remove(File $file): void
    {
        $this->getManagerFor($file)->remove($file);
    }

    public function getClass(): string
    {
        return $this->manager->getClass();
    }

    public function clear(): void
    {
        $this->manager->clear();

        foreach ($this->managers as $manager) {
            $manager->clear();
        }
    }
}
