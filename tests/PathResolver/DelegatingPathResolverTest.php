<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\PathResolver\DelegatingPathResolver;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Tests\File2;
use Arxy\FilesBundle\Tests\File3;
use PHPUnit\Framework\TestCase;

class DelegatingPathResolverTest extends TestCase
{
    private PathResolver $pathResolver1;
    private PathResolver $pathResolver2;
    private DelegatingPathResolver $pathResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pathResolver1 = $this->createMock(PathResolver::class);
        $this->pathResolver2 = $this->createMock(PathResolver::class);
        $this->pathResolver = new DelegatingPathResolver(
            [
                File::class => $this->pathResolver1,
                File2::class => $this->pathResolver2,
            ]
        );
    }

    public function testGetPath()
    {
        $file1 = new File('original_filename.jpg', 125, '1234567', 'image/jpeg');
        $this->pathResolver1->expects($this->once())->method('getPath')->with($file1)->willReturn(File::class);
        self::assertSame(File::class, $this->pathResolver->getPath($file1));

        $file2 = new File2('original_filename.jpg', 125, '1234567', 'image/jpeg');
        $this->pathResolver2->expects($this->once())->method('getPath')->with($file2)->willReturn(File2::class);
        self::assertSame(File2::class, $this->pathResolver->getPath($file2));
    }

    public function testNotManagedFile()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No resolver for '.File3::class);
        $this->pathResolver->getPath(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }
}
