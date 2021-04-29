<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Utility;

use Arxy\FilesBundle\Model\DecoratedFile;
use Arxy\FilesBundle\Model\File;
use DateTimeInterface;

class DownloadableFile extends DecoratedFile
{
    private ?string $name;
    private bool $forceDownload;
    private ?DateTimeInterface $expireAt;

    public function __construct(
        File $file,
        string $name = null,
        bool $forceDownload = false,
        DateTimeInterface $expireAt = null
    ) {
        parent::__construct($file);
        $this->name = $name;
        $this->forceDownload = $forceDownload;
        $this->expireAt = $expireAt;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isForceDownload(): bool
    {
        return $this->forceDownload;
    }

    public function getExpireAt(): ?DateTimeInterface
    {
        return $this->expireAt;
    }
}
