<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Entity;

use Arxy\FilesBundle\Entity\MutableFile as BaseFile;
use Arxy\FilesBundle\Model\MutablePathAware;
use Arxy\FilesBundle\Preview\PreviewableFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @implements PreviewableFile<Preview>
 */
class FileWithPreview extends BaseFile implements PreviewableFile, MutablePathAware
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
}
