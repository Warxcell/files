<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use DateInterval;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class CachePathResolver implements PathResolver
{
    private CacheItemPoolInterface $cache;
    /** @var int|DateInterval|null */
    private $expiresAfter;

    /**
     * @param PathResolver<T> $pathResolver
     * @param int|DateInterval|null $expiresAfter
     */
    public function __construct(
        private readonly PathResolver $pathResolver,
        CacheItemPoolInterface $cache,
        $expiresAfter = null
    ) {
        $this->cache = $cache;
        $this->expiresAfter = $expiresAfter;
    }

    #[\Override]
    public function getPath(File $file): string
    {
        $key = $file->getHash();
        /* @phpstan-ignore missingType.checkedException key is hash, does not violate key requirements */
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $item->expiresAfter($this->expiresAfter);
            $item->set($this->pathResolver->getPath($file));
            $this->cache->save($item);
        }

        /* @phpstan-ignore return.type */
        return $item->get();
    }
}
