<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Entity\File as AbstractFile;
use Arxy\FilesBundle\Model\MutablePathAware;

class PersistentPathFile extends AbstractFile implements MutablePathAware
{
    private ?int $id = null;
    private string $pathname;

    public function __construct(
        string $originalFilename,
        int $fileSize,
        string $hash,
        string $mimeType,
        string $pathname
    ) {
        parent::__construct($originalFilename, $fileSize, $hash, $mimeType);
        $this->pathname = $pathname;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getPathname(): string
    {
        return $this->pathname;
    }

    public function setPathname(string $pathname): void
    {
        $this->pathname = $pathname;
    }
}
