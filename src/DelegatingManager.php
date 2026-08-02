<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use InvalidArgumentException;
use LogicException;
use SplFileInfo;

use function array_merge;
use function count;
use function get_class;
use function reset;

/** @implements ManagerInterface<File, mixed> */
final class DelegatingManager implements ManagerInterface
{
    /** @var array<class-string<File>, ManagerInterface<File, mixed>> */
    private array $managers = [];
    /** @var ManagerInterface<File, mixed> */
    private ManagerInterface $manager;

    /**
     * @param ManagerInterface<File, mixed>[] $managers
     * @param ManagerInterface<File, mixed>|null $manager
     * @throws InvalidArgumentException
     */
    public function __construct(array $managers, ?ManagerInterface $manager = null)
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
     * @param class-string<File> $class
     * @return ManagerInterface<File, mixed>
     * @throws LogicException if not manager is found for $class
     */
    public function getManagerFor(string $class): ManagerInterface
    {
        if (!isset($this->managers[$class])) {
            throw new LogicException('No manager for ' . $class);
        }

        return $this->managers[$class];
    }

    #[\Override]
    public function upload(SplFileInfo $splFileInfo, mixed $context = null): File
    {
        return $this->manager->upload($splFileInfo, $context);
    }

    /**
     * @throws LogicException if not manager is found for $file
     */
    #[\Override]
    public function getPathname(File $file): string
    {
        return $this->getManagerForFile($file)->getPathname($file);
    }

    #[\Override]
    public function read(File $file): string
    {
        try {
            return $this->getManagerForFile($file)->read($file);
        } catch (LogicException $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    #[\Override]
    public function readStream(File $file)
    {
        try {
            return $this->getManagerForFile($file)->readStream($file);
        } catch (LogicException $exception) {
            throw FileException::unableToRead($file, $exception);
        }
    }

    #[\Override]
    public function write(MutableFile $file, SplFileInfo $splFileInfo): void
    {
        try {
            $this->getManagerForFile($file)->write($file, $splFileInfo);
        } catch (LogicException $exception) {
            throw FileException::unableToWrite($file, $exception);
        }
    }

    #[\Override]
    public function moveFile(File $file): void
    {
        try {
            $this->getManagerForFile($file)->moveFile($file);
        } catch (LogicException $exception) {
            throw FileException::unableToMove($file, $exception);
        }
    }

    #[\Override]
    public function remove(File $file): void
    {
        try {
            $this->getManagerForFile($file)->remove($file);
        } catch (LogicException $exception) {
            throw FileException::unableToRemove($file, $exception);
        }
    }

    #[\Override]
    public function getClass(): string
    {
        return $this->manager->getClass();
    }

    #[\Override]
    public function clear(): void
    {
        foreach (array_merge($this->managers, [$this->manager->getClass() => $this->manager]) as $manager) {
            $manager->clear();
        }
    }

    /**
     * @return ManagerInterface<File, mixed>
     * @throws LogicException if not manager is found for $file
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
