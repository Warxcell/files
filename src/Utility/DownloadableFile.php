<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Utility;

use Arxy\FilesBundle\Entity\MutableFile;
use Arxy\FilesBundle\Model\DecoratedFile;
use Arxy\FilesBundle\Model\File;
use DateTimeImmutable;
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

    public function getModifiedAt(): DateTimeImmutable
    {
        if ($this->decorated instanceof MutableFile) {
            return $this->decorated->getModifiedAt();
        } else {
            return $this->decorated->getCreatedAt();
        }
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
