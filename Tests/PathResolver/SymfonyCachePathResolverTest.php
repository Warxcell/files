<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

class SymfonyCachePathResolverTest extends TestCase
{
    private PathResolver\SymfonyCachePathResolver $pathResolver;
    private PathResolver $decoratedPathResolver;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedPathResolver = new class implements PathResolver {
            private bool $called = false;

            public function getPath(\Arxy\FilesBundle\Model\File $file): string
            {
                if ($this->called) {
                    throw new \LogicException('This should be cached');
                }

                $this->called = true;

                return (string)$file->getId();
            }
        };
        $this->cache = new ArrayAdapter();

        $this->pathResolver = new PathResolver\SymfonyCachePathResolver(
            $this->decoratedPathResolver,
            $this->cache
        );
    }

    public function testGetPath()
    {
        $file = new File();
        $file->setId(1);
        $file->setMd5Hash('1234567');

        $this->assertFalse($this->cache->hasItem('1234567'));
        $this->assertSame('1', $this->pathResolver->getPath($file));
        $this->assertTrue($this->cache->hasItem('1234567'));
        $this->assertSame('1', $this->pathResolver->getPath($file));
    }
}
