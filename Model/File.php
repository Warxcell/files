<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

abstract class File
{
    /**
     * @var string|null
     */
    protected $originalFilename;

    /**
     * @var string|null
     */
    protected $fileSize;

    /**
     * @var string|null
     */
    protected $md5Hash;

    /**
     * @var \DateTimeImmutable
     */
    protected $createdAt;

    /**
     * @var string|null
     */
    protected $mimeType;

    abstract public function getId();

    public function __construct()
    {
        $this->setCreatedAt(new \DateTimeImmutable());
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getMd5Hash(): ?string
    {
        return $this->md5Hash;
    }

    public function setMd5Hash(string $md5Hash): void
    {
        $this->md5Hash = $md5Hash;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}