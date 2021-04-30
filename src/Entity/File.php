<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Entity;

use Arxy\FilesBundle\Model\AbstractFile;
use Arxy\FilesBundle\Model\IdentifiableFile;

abstract class File extends AbstractFile implements IdentifiableFile
{
    abstract public function getId();
}
