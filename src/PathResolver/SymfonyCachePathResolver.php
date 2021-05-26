<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use DateInterval;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SymfonyCachePathResolver implements PathResolver
{
    private PathResolver $pathResolver;
    private CacheInterface $cache;
    /** @var int|DateInterval|null */
    private $expiresAfter;

    /**
     * @param int|DateInterval|null $expiresAfter
     */
    public function __construct(PathResolver $pathResolver, CacheInterface $cache, $expiresAfter = null)
    {
        $this->pathResolver = $pathResolver;
        $this->cache = $cache;
        $this->expiresAfter = $expiresAfter;
    }

    public function getPath(File $file): string
    {
        $key = $file->getHash();

        return $this->cache->get(
            $key,
            function (ItemInterface $item) use ($file): string {
                $item->expiresAfter($this->expiresAfter);

                return $this->pathResolver->getPath($file);
            }
        );
    }
}
