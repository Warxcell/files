<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Twig;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Twig\Extension\RuntimeExtensionInterface;

class FilesRuntime implements RuntimeExtensionInterface
{
    private ManagerInterface $manager;

    public function __construct(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function readContent(File $file): string
    {
        return $this->manager->read($file);
    }
}
