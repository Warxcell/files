<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Entity;

use Arxy\FilesBundle\Entity\File as BaseFile;
use Arxy\FilesBundle\Model\MutablePathAware;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Preview extends BaseFile implements MutablePathAware
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string")
     */
    private string $pathname;

    public function getId(): ?int
    {
        return $this->id;
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
