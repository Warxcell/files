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

    public function testGetFunctions(): void
    {
        $twigFunctions = $this->extension->getFunctions();
        self::assertCount(1, $twigFunctions);

        self::assertInstanceOf(TwigFunction::class, $twigFunctions[0]);
        self::assertSame('file_path', $twigFunctions[0]->getName());
        self::assertSame($this->extension, $twigFunctions[0]->getCallable()[0]);
        self::assertSame('filePath', $twigFunctions[0]->getCallable()[1]);
    }

    public function testFilePath(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->pathResolver->expects($this->once())->method('getPath')->with($file)->willReturn('all good');

        self::assertSame('all good', $this->extension->filePath($file));
    }
}
