<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Entity;

use Arxy\FilesBundle\Model\AbstractFile;
use Arxy\FilesBundle\Model\MutablePathAware;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class EmbeddableFilePersistentPath extends AbstractFile implements MutablePathAware
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $pathname = null;

    public function getPathname(): string
    {
        return $this->pathname;
    }

    public function setPathname(string $pathname): void
    {
        $this->pathname = $pathname;
    }
}
