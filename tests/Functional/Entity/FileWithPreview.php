<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Entity;

use Arxy\FilesBundle\Entity\File as BaseFile;
use Arxy\FilesBundle\Preview\PreviewableFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FileWithPreview extends BaseFile implements PreviewableFile
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
}
