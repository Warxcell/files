<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;

interface ManagerInterface
{
    public function upload(\SplFileInfo $file): File;

    public function getPathname(): string;

    public function read(File $file): string;

    public function readStream(File $file);

    public function refresh(File $file): void;

    public function migrate(File $file, NamingStrategy $oldStrategy): bool;

    public function moveFile(File $file): void;

    public function remove(File $entity): void;

    public function getClass(): string;
}
