<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Utility;

use Arxy\FilesBundle\Model\DecoratedFile;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * @template T of File
 * @extends DecoratedFile<T>
 */
class DownloadableFile extends DecoratedFile
{
    /**
     * @param T $file
     */
    public function __construct(
        File $file,
        private readonly ?string $name = null,
        private readonly bool $forceDownload = false,
        private readonly ?DateTimeInterface $expireAt = null
    ) {
        parent::__construct($file);
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
