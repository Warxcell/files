<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\PathResolverManager;
use PHPUnit\Framework\TestCase;

class PathResolverManagerTest extends TestCase
{
    private ManagerInterface $decorated;
    private PathResolver $pathResolver;
    private ManagerInterface $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decorated = $this->createMock(ManagerInterface::class);
        $this->pathResolver = $this->createMock(PathResolver::class);

        $this->decorator = new PathResolverManager($this->decorated, $this->pathResolver);
    }

    public function testUpload()
    {
        $file = new \SplFileObject(__DIR__.'/files/image1.jpg');
        $uploadedFile = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects($this->once())->method('upload')->with($file)->willReturn($uploadedFile);
        $actualFile = $this->decorator->upload($file);
        self::assertSame($uploadedFile, $actualFile);
    }

    public function testGetPathname()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects($this->once())->method('getPathname')->with($file)->willReturn('!!!');

        $actual = $this->decorator->getPathname($file);
        self::assertSame('!!!', $actual);
    }

    public function testRead()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects($this->once())->method('read')->with($file)->willReturn('!!!');

        $actual = $this->decorator->read($file);
        self::assertSame('!!!', $actual);
    }

    public function testReadStream()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects($this->once())->method('readStream')->with($file)->willReturn('!!!');

        $actual = $this->decorator->readStream($file);
        self::assertSame('!!!', $actual);
    }

    public function testRefresh()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects($this->once())->method('refresh')->with($file);

        $this->decorator->refresh($file);
    }

    public function testMigrate()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $namingStrategy = $this->createMock(NamingStrategy::class);
        $this->decorated->expects($this->once())->method('migrate')->with($file, $namingStrategy)->willReturn(true);

        $result = $this->decorator->migrate($file, $namingStrategy);
        self::assertTrue($result);
    }

    public function testMove()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects($this->once())->method('moveFile')->with($file);

        $this->decorator->moveFile($file);
    }

    public function testRemove()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->decorated->expects($this->once())->method('remove')->with($file);

        $this->decorator->remove($file);
    }

    public function testGetClass()
    {
        $this->decorated->expects($this->once())->method('getClass')->willReturn('!!!');

        $class = $this->decorator->getClass();

        self::assertSame('!!!', $class);
    }

    public function testClear()
    {
        $this->decorated->expects($this->once())->method('clear');
        $this->decorator->clear();
    }

    public function testGetPath()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->pathResolver->expects($this->once())->method('getPath')->with($file)->willReturn('!!!');

        $actual = $this->decorator->getPath($file);

        self::assertSame('!!!', $actual);
    }
}
