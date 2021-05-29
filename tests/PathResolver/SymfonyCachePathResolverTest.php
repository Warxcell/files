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

        $this->cache = new ArrayAdapter();
        $this->decoratedPathResolver = $this->createMock(PathResolver::class);
        $this->pathResolver = new PathResolver\SymfonyCachePathResolver(
            $this->decoratedPathResolver,
            $this->cache
        );
    }

    public function testGetPath(): void
    {
        $file = new File('original_filename.jpg', 125, '1234567', 'image/jpeg');
        $file->setId(1);

        $this->decoratedPathResolver->expects($this->once())->method('getPath')->with($file)->willReturn('path');

        self::assertFalse($this->cache->hasItem('1234567'));
        self::assertSame('path', $this->pathResolver->getPath($file));
        self::assertTrue($this->cache->hasItem('1234567'));
        self::assertSame('path', $this->pathResolver->getPath($file));
    }
}
