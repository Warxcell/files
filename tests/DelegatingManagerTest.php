<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\DelegatingManager;
use Arxy\FilesBundle\InvalidArgumentException;
use Arxy\FilesBundle\LiipImagine\FileFilter;
use Arxy\FilesBundle\ManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

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

    public function testZeroManagers()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You should pass at least one manager!');

        new DelegatingManager([]);
    }

    public function testRead()
    {
        $file1 = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2 = new File2('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager1->expects(self::once())->method('read')->with($file1)->willReturn('manager1Read');
        $this->manager2->expects(self::once())->method('read')->with($file2)->willReturn('manager2Read');

        self::assertSame('manager1Read', $this->manager->read($file1));
        self::assertSame('manager2Read', $this->manager->read($file2));
    }

    public function testReadStream()
    {
        $file1 = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2 = new File2('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager1->expects(self::once())->method('readStream')->with($file1)->willReturn('manager1Read');
        $this->manager2->expects(self::once())->method('readStream')->with($file2)->willReturn('manager2Read');

        self::assertSame('manager1Read', $this->manager->readStream($file1));
        self::assertSame('manager2Read', $this->manager->readStream($file2));
    }

    public function testGetPathname()
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

    public function testNoManagerForFileRead()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->read(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileReadStream()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->readStream(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileWrite()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->readStream(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileWriteStream()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->readStream(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFilePathname()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->getPathname(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileMove()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->moveFile(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForRemove()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->remove(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testGetClass()
    {
        self::assertEquals($this->manager1->getClass(), $this->manager->getClass());
    }

    public function testMainManager()
    {
        $forUpload = new \SplFileInfo(__DIR__.'/files/image1.jpg');
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

    public function testClear()
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
