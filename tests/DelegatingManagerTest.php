<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\DelegatingManager;
use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\LiipImagine\FileFilter;
use Arxy\FilesBundle\ManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use stdClass;

class DelegatingManagerTest extends TestCase
{
    private ManagerInterface $manager1;
    private ManagerInterface $manager2;
    private ManagerInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager1 = $this->createMock(ManagerInterface::class);
        $this->manager1->method('getClass')->willReturn(File::class);

        $this->manager2 = $this->createMock(ManagerInterface::class);
        $this->manager2->method('getClass')->willReturn(File2::class);

        $this->manager = new DelegatingManager(
            [
                $this->manager1,
                $this->manager2,
            ]
        );
    }

    public function testZeroManagers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You should pass at least one manager!');

        new DelegatingManager([]);
    }

    public function testRead(): void
    {
        $file1 = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2 = new File2('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager1->expects(self::once())->method('read')->with($file1)->willReturn('manager1Read');
        $this->manager2->expects(self::once())->method('read')->with($file2)->willReturn('manager2Read');

        self::assertSame('manager1Read', $this->manager->read($file1));
        self::assertSame('manager2Read', $this->manager->read($file2));
    }

    public function testReadStream(): void
    {
        $file1 = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2 = new File2('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager1->expects(self::once())->method('readStream')->with($file1)->willReturn('manager1Read');
        $this->manager2->expects(self::once())->method('readStream')->with($file2)->willReturn('manager2Read');

        self::assertSame('manager1Read', $this->manager->readStream($file1));
        self::assertSame('manager2Read', $this->manager->readStream($file2));
    }

    public function testGetPathname(): void
    {
        $file1 = new File('original_filename.jpg', 125, '1234567', 'image/jpeg');
        $file1->setId(1);
        $this->manager1->expects(self::exactly(3))->method('getPathname')->with($file1)->willReturn('manager1_file1');

        self::assertSame('manager1_file1', $this->manager->getPathname($file1));
        self::assertSame('manager1_file1', $this->manager->getPathname(new VirtualFile($file1)));
        self::assertSame(
            'manager1_file1',
            $this->manager->getPathname(new FileFilter(new VirtualFile($file1), 'filter'))
        );

        $file2 = new File2('original_filename.jpg', 125, '1234567', 'image/jpeg');
        $file2->setId(1);
        $this->manager2->expects(self::exactly(3))->method('getPathname')->with($file2)->willReturn('manager2_file1');

        self::assertSame('manager2_file1', $this->manager->getPathname($file2));
        self::assertSame('manager2_file1', $this->manager->getPathname(new VirtualFile($file2)));
        self::assertSame(
            'manager2_file1',
            $this->manager->getPathname(new FileFilter(new VirtualFile($file2), 'filter'))
        );
    }

    public function testWrite(): void
    {
        $file1 = new File('original_filename.jpg', 125, '1234567', 'image/jpeg');

        $this->manager1->expects(self::once())->method('write')->with($file1, 'test');

        $this->manager->write($file1, 'test');
    }

    public function testWriteStream(): void
    {
        $file1 = new File('original_filename.jpg', 125, '1234567', 'image/jpeg');
        $stream = fopen('data://text/plain,test', 'r');

        $this->manager1->expects(self::once())->method('writeStream')->with($file1, $stream);

        $this->manager->writeStream($file1, $stream);
    }

    public function testNoManagerForFileRead(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->read(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileReadStream(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->readStream(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileWrite(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->readStream(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileWriteStream(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->readStream(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFilePathname(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->getPathname(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileMove(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->moveFile(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForRemove(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->remove(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testGetClass(): void
    {
        self::assertEquals($this->manager1->getClass(), $this->manager->getClass());
    }

    public function testMainManager(): void
    {
        $forUpload = new SplFileInfo(__DIR__.'/files/image1.jpg');
        $uploadedFile = new File2('original_filename.jpg', 125, '1234567', 'image/jpeg');

        $manager1 = $this->createMock(ManagerInterface::class);

        $manager2 = $this->createMock(ManagerInterface::class);
        $manager2->expects($this->once())->method('upload')->with($forUpload)->willReturn($uploadedFile);

        $manager = new DelegatingManager(
            [
                $manager1,
            ],
            $manager2,
        );

        $actualFile = $manager->upload($forUpload);
        self::assertSame($uploadedFile, $actualFile);
    }

    public function testGetManagerFor()
    {
        $manager1 = $this->createMock(ManagerInterface::class);
        $manager1->method('getClass')->willReturn(File::class);
        $manager2 = $this->createMock(ManagerInterface::class);
        $manager2->method('getClass')->willReturn(File2::class);

        $manager = new DelegatingManager(
            [
                $manager1,
                $manager2,
            ]
        );

        self::assertSame($manager1, $manager->getManagerFor(File::class));
        self::assertSame($manager2, $manager->getManagerFor(File2::class));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for stdClass');
        $manager->getManagerFor(stdClass::class);
    }

    public function testClear(): void
    {
        $manager1 = $this->createMock(ManagerInterface::class);
        $manager1->expects($this->once())->method('clear');
        $manager2 = $this->createMock(ManagerInterface::class);
        $manager2->expects($this->once())->method('clear');
        $manager3 = $this->createMock(ManagerInterface::class);
        $manager3->expects($this->once())->method('clear');

        $manager = new DelegatingManager([$manager1, $manager2], $manager3);
        $manager->clear();
    }
}
