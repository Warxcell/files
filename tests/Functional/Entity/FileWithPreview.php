<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Entity;

use Arxy\FilesBundle\Entity\File as BaseFile;
use Arxy\FilesBundle\Model\MutableFile;
use Arxy\FilesBundle\Model\MutablePathAware;
use Arxy\FilesBundle\Preview\PreviewableFile;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FileWithPreview extends BaseFile implements PreviewableFile, MutablePathAware, MutableFile
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private ?int $id = null;

    /**
     * @ORM\OneToOne(targetEntity=Preview::class, cascade={"PERSIST"})
     */
    private ?Preview $preview = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $pathname;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPreview(): ?\Arxy\FilesBundle\Model\File
    {
        return $this->preview;
    }

    public function setPreview(?\Arxy\FilesBundle\Model\File $file): void
    {
        assert($file instanceof Preview);
        $this->preview = $file;
    }

    public function getPathname(): string
    {
        return $this->pathname;
    }

    public function setPathname(string $pathname): void
    {
        $this->pathname = $pathname;
    }

    public function setOriginalFilename(string $originalFilename): void
    {
        $this->originalFilename = $originalFilename;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function setMd5Hash(string $md5Hash): void
    {
        $this->md5Hash = $md5Hash;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }
}
