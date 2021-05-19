<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Entity;

use Arxy\FilesBundle\Entity\File as BaseFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Preview extends BaseFile
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
