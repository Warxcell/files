<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\LiipImagine;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class FileFilterPathResolver implements PathResolver
{
    private ManagerInterface $fileManager;
    private CacheManager $cacheManager;

    public function __construct(ManagerInterface $fileManager, CacheManager $cacheManager)
    {
        $this->fileManager = $fileManager;
        $this->cacheManager = $cacheManager;
    }

    public function getPath(File $file): string
    {
        if (!$file instanceof FileFilter) {
            throw new \InvalidArgumentException(
                __CLASS__.' supports only '.FileFilter::class.', '.get_debug_type($file).' passed'
            );
        }

        return $this->cacheManager->getBrowserPath($this->fileManager->getPathname($file), $file->getFilter());
    }
}