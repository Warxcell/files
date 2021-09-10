<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\PathResolverManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileObject;
use SplTempFileObject;

class PathResolverManagerTest extends TestCase
{
    /** @var ManagerInterface & MockObject */
    private ManagerInterface $decorated;
    /** @var PathResolver & MockObject */
    private PathResolver $pathResolver;
    private PathResolverManager $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decorated = $this->createMock(ManagerInterface::class);
        $this->pathResolver = $this->createMock(PathResolver::class);
        $this->decorator = new PathResolverManager($this->decorated, $this->pathResolver);
    }

    public function testUpload(): void
    {
        $file = new SplFileObject(__DIR__ . '/files/image1.jpg');
        $uploadedFile = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects(self::once())->method('upload')->with($file)->willReturn($uploadedFile);
        $actualFile = $this->decorator->upload($file);
        self::assertSame($uploadedFile, $actualFile);
    }

    public function testGetPathname(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects(self::once())->method('getPathname')->with($file)->willReturn('!!!');

        $actual = $this->decorator->getPathname($file);
        self::assertSame('!!!', $actual);
    }

    public function testRead(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects(self::once())->method('read')->with($file)->willReturn('!!!');

        $actual = $this->decorator->read($file);
        self::assertSame('!!!', $actual);
    }

    public function testReadStream(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects(self::once())->method('readStream')->with($file)->willReturn('!!!');

        $actual = $this->decorator->readStream($file);
        self::assertSame('!!!', $actual);
    }

    public function testMove(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects(self::once())->method('moveFile')->with($file);

        $this->decorator->moveFile($file);
    }

    public function testRemove(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects(self::once())->method('remove')->with($file);

        $this->decorator->remove($file);
    }

    public function testWrite(): void
    {
        $file = new MutableFile('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $splTemp = new SplTempFileObject();
        $splTemp->fwrite('test');
        $this->decorated->expects(self::once())->method('write')->with($file, $splTemp);

        $this->decorator->write($file, $splTemp);
    }

    public function testGetClass(): void
    {
        $this->decorated->expects(self::once())->method('getClass')->willReturn('!!!');

        $class = $this->decorator->getClass();

        self::assertSame('!!!', $class);
    }

    public function testClear(): void
    {
        $this->decorated->expects(self::once())->method('clear');
        $this->decorator->clear();
    }

    public function testGetPath(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->pathResolver->expects(self::once())->method('getPath')->with($file)->willReturn('!!!');

        $actual = $this->decorator->getPath($file);

        self::assertSame('!!!', $actual);
    }
}
