<?php


namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use League\Flysystem\FilesystemOperator;

class Migrator implements MigratorInterface
{
    private FilesystemOperator $filesystem;
    private NamingStrategy $old;
    private NamingStrategy $new;

    public function __construct(FilesystemOperator $filesystem, NamingStrategy $oldNamingStrategy, NamingStrategy $newNamingStrategy)
    {
        $this->filesystem = $filesystem;
        $this->old = $oldNamingStrategy;
        $this->new = $newNamingStrategy;
    }

    private function getPathname(File $file, NamingStrategy $namingStrategy): string
    {
        return ($namingStrategy->getDirectoryName($file) ?? "").$namingStrategy->getFileName($file);
    }

    public function migrate(File $file): bool
    {
        $oldName = $this->getPathname($file, $this->old);

        if (!$this->filesystem->fileExists($oldName)) {
            return false;
        }

        $newName = $this->getPathname($file, $this->new);

        $this->filesystem->move($oldName, $newName);

        return true;
    }
}
