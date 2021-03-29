<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Twig;

use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Twig\PathResolverExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class PathResolverExtensionTest extends TestCase
{
    private PathResolver $pathResolver;
    private PathResolverExtension $extension;

    protected function setUp(): void
    {
        $this->pathResolver = $this->createMock(PathResolver::class);
        $this->extension = new PathResolverExtension($this->pathResolver);
    }

    public function testGetFunctions()
    {
        $twigFunctions = $this->extension->getFunctions();
        $this->assertCount(1, $twigFunctions);

        $this->assertInstanceOf(TwigFunction::class, $twigFunctions[0]);
        $this->assertSame('file_path', $twigFunctions[0]->getName());
        $this->assertSame($this->extension, $twigFunctions[0]->getCallable()[0]);
        $this->assertSame('filePath', $twigFunctions[0]->getCallable()[1]);
    }

    public function testFilePath()
    {
        $file = new File();
        $this->pathResolver->expects($this->once())->method('getPath')->with($file)->willReturn('all good');

        $this->assertSame('all good', $this->extension->filePath($file));
    }
}
