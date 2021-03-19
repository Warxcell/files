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
    private DelegatingPathResolver $pathResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pathResolver = new DelegatingPathResolver(
            [
                File::class => new class implements PathResolver {
                    public function getPath(\Arxy\FilesBundle\Model\File $file): string
                    {
                        if (!$file instanceof File) {
                            throw new \LogicException('Expected '.File::class);
                        }

                        return get_class($file);
                    }
                },
                File2::class => new class implements PathResolver {
                    public function getPath(\Arxy\FilesBundle\Model\File $file): string
                    {
                        if (!$file instanceof File2) {
                            throw new \LogicException('Expected '.File2::class);
                        }

                        return get_class($file);
                    }
                },
            ]
        );
    }

    public function testGetPath()
    {
        $this->assertSame(File::class, $this->pathResolver->getPath(new File()));
        $this->assertSame(File2::class, $this->pathResolver->getPath(new File2()));
    }

    public function testNotManagedFile()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No resolver for '.File3::class);
        $this->pathResolver->getPath(new File3());
    }
}
