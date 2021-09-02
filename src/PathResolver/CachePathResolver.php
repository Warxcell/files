<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use DateInterval;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CachePathResolver implements PathResolver
{
    private PathResolver $pathResolver;
    private CacheItemPoolInterface $cache;
    /** @var int|DateInterval|null */
    private $expiresAfter;

    /**
     * @param int|DateInterval|null $expiresAfter
     */
    public function __construct(PathResolver $pathResolver, CacheItemPoolInterface $cache, $expiresAfter = null)
    {
        $this->pathResolver = $pathResolver;
        $this->cache = $cache;
        $this->expiresAfter = $expiresAfter;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getPath(File $file): string
    {
        $key = $file->getHash();
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $item->expiresAfter($this->expiresAfter);
            $item->set($this->pathResolver->getPath($file));
            $this->cache->save($item);
        }

        return $item->get();
    }
}
