<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use Symfony\Component\Uid\Uuid;

final class UuidV5Strategy implements NamingStrategy
{
    private Uuid $namespace;

    public function __construct(Uuid $namespace)
    {
        $this->namespace = $namespace;
    }

    public function getDirectoryName(File $file): ?string
    {
        return null;
    }

    public function getFileName(File $file): string
    {
        return (string)Uuid::v5($this->namespace, $file->getMd5Hash());
    }
}
