<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Entity;

use Arxy\FilesBundle\Entity\EmbeddableFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class News
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=File::class, cascade={"ALL"})
     */
    private ?File $file = null;

    /** @ORM\Embedded(class=EmbeddableFile::class) */
    private ?EmbeddableFile $embeddableFile = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getEmbeddableFile(): ?EmbeddableFile
    {
        return $this->embeddableFile;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setFile(?File $file): void
    {
        $this->file = $file;
    }

    public function setEmbeddableFile(?EmbeddableFile $embeddableFile): void
    {
        $this->embeddableFile = $embeddableFile;
    }
}