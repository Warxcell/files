<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\DelegatingManager;
use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class DelegatingManagerTest extends TestCase
{
    private ManagerInterface $manager1;
    private ManagerInterface $manager2;
    private ManagerInterface $manager;
    private FilesystemOperator $filesystem1;
    private FilesystemOperator $filesystem2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem1 = new Filesystem(new InMemoryFilesystemAdapter());
        $this->filesystem2 = new Filesystem(new InMemoryFilesystemAdapter());

        $this->manager1 = new Manager(
            File::class,
            new FileRepository(),
            $this->filesystem1,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return null;
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return (string)$file->getId();
                }
            }
        );

        $this->manager2 = new Manager(
            File2::class,
            new FileRepository(),
            $this->filesystem2,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return null;
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return (string)$file->getId();
                }
            }
        );

        $this->manager = new DelegatingManager(
            [
                $this->manager1,
                $this->manager2,
            ]
        );
    }

    public function testRead()
    {
        $forUpload1 = __DIR__.'/files/image1.jpg';
        /** @var File $file1 */
        $file1 = $this->manager1->upload(new \SplFileObject($forUpload1));
        $this->manager1->moveFile($file1);

        $forUpload2 = __DIR__.'/files/image2.jpg';
        /** @var File2 $file2 */
        $file2 = $this->manager2->upload(new \SplFileObject($forUpload2));
        $this->manager2->moveFile($file2);

        $this->assertEquals(md5_file($forUpload1), md5($this->manager->read($file1)));
        $this->assertEquals(md5_file($forUpload2), md5($this->manager->read($file2)));
    }

    public function testReadStream()
    {
        $forUpload1 = __DIR__.'/files/image1.jpg';
        /** @var File $file1 */
        $file1 = $this->manager1->upload(new \SplFileObject($forUpload1));
        $this->manager1->moveFile($file1);

        $forUpload2 = __DIR__.'/files/image2.jpg';
        /** @var File2 $file2 */
        $file2 = $this->manager2->upload(new \SplFileObject($forUpload2));
        $this->manager2->moveFile($file2);

        $this->assertEquals(md5_file($forUpload1), md5(stream_get_contents($this->manager->readStream($file1))));
        $this->assertEquals(md5_file($forUpload2), md5(stream_get_contents($this->manager->readStream($file2))));
    }

    public function testNoManagerForFileRead()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->read(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileReadStream()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->readStream(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFilePathname()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->getPathname(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileRefresh()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->refresh(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForFileMigrate()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->migrate(
            new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'),
            $this->createMock(NamingStrategy::class)
        );
    }

    public function testNoManagerForFileMove()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->moveFile(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testNoManagerForRemove()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager for Arxy\FilesBundle\Tests\File3');
        $this->manager->remove(new File3('original_filename.jpg', 125, '1234567', 'image/jpeg'));
    }

    public function testGetClass()
    {
        $this->assertEquals($this->manager1->getClass(), $this->manager->getClass());
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
        $this->assertSame($uploadedFile, $actualFile);
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
