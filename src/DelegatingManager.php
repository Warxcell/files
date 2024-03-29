<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use InvalidArgumentException;
use LogicException;
use SplFileInfo;

use function array_merge;
use function get_class;
use function reset;

final class DelegatingManager implements ManagerInterface
{
    /** @var array<class-string<File>, ManagerInterface> */
    private array $managers = [];
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

    /**
     * @param class-string<T> $class
     * @return ManagerInterface<T>
     * @throws LogicException if not manager is found for $class
     * @template T of File
     */
    public function getManagerFor(string $class): ManagerInterface
    {
        if (!isset($this->managers[$class])) {
            throw new LogicException('No manager for ' . $class);
        }

        return $this->managers[$class];
    }

    public function upload(SplFileInfo $splFileInfo): File
    {
        return $this->manager->upload($splFileInfo);
    }

    public function getPathname(File $file): string
    {
        return $this->getManagerForFile($file)->getPathname($file);
    }

    public function read(File $file): string
    {
        return $this->getManagerForFile($file)->read($file);
    }

    public function readStream(File $file)
    {
        return $this->getManagerForFile($file)->readStream($file);
    }

    public function write(MutableFile $file, SplFileInfo $splFileInfo): void
    {
        $this->getManagerForFile($file)->write($file, $splFileInfo);
    }

    public function moveFile(File $file): void
    {
        $this->getManagerForFile($file)->moveFile($file);
    }

    public function remove(File $file): void
    {
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

    /**
     * @param T $file
     * @return ManagerInterface<T>
     * @throws LogicException if not manager is found for $file
     * @template T of File
     */
    private function getManagerForFile(File $file): ManagerInterface
    {
        foreach ($this->managers as $class => $manager) {
            if ($file instanceof $class) {
                return $manager;
            }
        }
        throw new LogicException('No manager for ' . get_class($file));
    }
}
