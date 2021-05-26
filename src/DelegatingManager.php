<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\DecoratedFile;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use LogicException;
use SplFileInfo;
use function reset;

final class DelegatingManager implements ManagerInterface
{
    /** @var ManagerInterface[] */
    private array $managers;
    private ManagerInterface $manager;

    public function __construct(array $managers, ManagerInterface $manager = null)
    {
        if (count($managers) === 0) {
            throw new InvalidArgumentException('You should pass at least one manager!');
        }
        $this->managers = $managers;

        if ($manager !== null) {
            $this->manager = $manager;
        } else {
            $this->manager = reset($managers);
        }
    }

    private function getFile(File $file): File
    {
        while ($file instanceof DecoratedFile) {
            $file = $file->getDecorated();
        }

        return $file;
    }

    private function getManagerFor(File $file): ManagerInterface
    {
        foreach ($this->managers as $manager) {
            $class = $manager->getClass();
            if ($file instanceof $class) {
                return $manager;
            }
        }
        throw new LogicException('No manager for '.get_class($file));
    }

    public function upload(SplFileInfo $file): File
    {
        return $this->manager->upload($file);
    }

    public function getPathname(File $file): string
    {
        $file = $this->getFile($file);

        return $this->getManagerFor($file)->getPathname($file);
    }

    public function read(File $file): string
    {
        $file = $this->getFile($file);

        return $this->getManagerFor($file)->read($file);
    }

    public function readStream(File $file)
    {
        $file = $this->getFile($file);

        return $this->getManagerFor($file)->readStream($file);
    }

    public function write(MutableFile $file, string $contents): void
    {
        $file = $this->getFile($file);
        assert($file instanceof MutableFile);
        $this->getManagerFor($file)->write($file, $contents);
    }

    public function writeStream(MutableFile $file, $resource): void
    {
        $file = $this->getFile($file);
        assert($file instanceof MutableFile);
        $this->getManagerFor($file)->writeStream($file, $resource);
    }

    public function moveFile(File $file): void
    {
        $file = $this->getFile($file);
        $this->getManagerFor($file)->moveFile($file);
    }

    public function remove(File $file): void
    {
        $file = $this->getFile($file);
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
