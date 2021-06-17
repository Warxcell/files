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

    /**
     * @param ManagerInterface[] $managers
     */
    public function __construct(array $managers, ManagerInterface $manager = null)
    {
        if (count($managers) === 0) {
            throw new InvalidArgumentException('You should pass at least one manager!');
        }
        if ($manager !== null) {
            $this->manager = $manager;
        } else {
            $this->manager = reset($managers);
        }

        foreach ($managers as $manager) {
            $this->managers[$manager->getClass()] = $manager;
        }
    }

    private function getFile(File $file): File
    {
        while ($file instanceof DecoratedFile) {
            $file = $file->getDecorated();
        }

        return $file;
    }

    /**
     * @throws LogicException if not manager is found for $class
     */
    public function getManagerFor(string $class): ManagerInterface
    {
        if (!isset($this->managers[$class])) {
            throw new LogicException('No manager for '.$class);
        }

        return $this->managers[$class];
    }

    private function getManagerForFile(File $file): ManagerInterface
    {
        foreach ($this->managers as $class => $manager) {
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

        return $this->getManagerForFile($file)->getPathname($file);
    }

    public function read(File $file): string
    {
        $file = $this->getFile($file);

        return $this->getManagerForFile($file)->read($file);
    }

    public function readStream(File $file)
    {
        $file = $this->getFile($file);

        return $this->getManagerForFile($file)->readStream($file);
    }

    public function write(MutableFile $file, string $contents): void
    {
        $file = $this->getFile($file);
        assert($file instanceof MutableFile);
        $this->getManagerForFile($file)->write($file, $contents);
    }

    public function writeStream(MutableFile $file, $resource): void
    {
        $file = $this->getFile($file);
        assert($file instanceof MutableFile);
        $this->getManagerForFile($file)->writeStream($file, $resource);
    }

    public function moveFile(File $file): void
    {
        $file = $this->getFile($file);
        $this->getManagerForFile($file)->moveFile($file);
    }

    public function remove(File $file): void
    {
        $file = $this->getFile($file);
        $this->getManagerForFile($file)->remove($file);
    }

    public function getClass(): string
    {
        return $this->manager->getClass();
    }

    public function clear(): void
    {
        foreach (array_merge($this->managers, [$this->manager->getClass() => $this->manager]) as $manager) {
            $manager->clear();
        }
    }
}
