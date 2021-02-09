<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Symfony\Component\Asset\Packages;

class AssetsPathResolver implements PathResolver
{
    /** @var ManagerInterface */
    private $manager;
    /** @var Packages */
    private $packages;
    /** @var string|null */
    private $package;

    public function __construct(ManagerInterface $manager, Packages $packages, string $package = null)
    {
        $this->manager = $manager;
        $this->packages = $packages;
        $this->package = $package;
    }

    public function getPath(File $file): string
    {
        return $this->packages->getUrl($this->manager->getPathname($file), $this->package);
    }
}