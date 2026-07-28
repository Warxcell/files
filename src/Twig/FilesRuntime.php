<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Twig\Extension\RuntimeExtensionInterface;

class FilesRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ManagerInterface $manager
    ) {
    }

    /**
     * @throws \Arxy\FilesBundle\FileException
     */
    public function readContent(File $file): string
    {
        return $this->manager->read($file);
    }
}
