<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Symfony\Component\Asset\Packages;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class AssetsPathResolver implements PathResolver
{
    /**
     * @param ManagerInterface<T, mixed> $manager
     */
    public function __construct(
        private readonly ManagerInterface $manager,
        private readonly Packages $packages,
        private readonly ?string $package = null
    ) {
    }

    #[\Override]
    public function getPath(File $file): string
    {
        return $this->packages->getUrl($this->manager->getPathname($file), $this->package);
    }
}
