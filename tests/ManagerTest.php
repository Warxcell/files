<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Event\FileUploaded;
use Arxy\FilesBundle\Event\PreRemove;
use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Repository;
use DateTimeImmutable;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\MimeTypeDetection\MimeTypeDetector;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use SplFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ManagerTest extends TestCase
{
    private ManagerInterface $manager;
    private FilesystemOperator $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $this->manager = new Manager(
            File::class,
            $this->filesystem,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return null;
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return (string)$file->getId();
                }
            },
            new FileRepository(),
        );
    }

    public function testUploadEvent()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $manager = new Manager(
            File::class,
            $this->createMock(FilesystemOperator::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            $dispatcher
        );

        $dispatcher->expects(self::once())->method('dispatch')->with(
            self::callback(
                static fn(FileUploaded $fileUploaded): bool => $fileUploaded->getFile(
                    ) instanceof File && $fileUploaded->getManager() === $manager
            )
        );

        /** @var File $file */
        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
    }

    public function testPreRemove()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $manager = new Manager(
            File::class,
            $this->createMock(FilesystemOperator::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            $dispatcher
        );

        $dispatcher->expects(self::exactly(2))->method('dispatch')->withConsecutive(
            [
                self::callback(
                    static fn(FileUploaded $fileUploaded): bool => $fileUploaded->getFile(
                        ) instanceof File && $fileUploaded->getManager() === $manager
                ),
            ],
            [
                self::callback(
                    static fn(PreRemove $preRemove): bool => $preRemove->getFile(
                        ) instanceof File && $preRemove->getManager() === $manager
                ),
            ]
        );

        /** @var File $file */
        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $manager->remove($file);
    }

    public function testSimpleUpload()
    {
        self::assertEquals(File::class, $this->manager->getClass());
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));

        self::assertTrue($file instanceof File);
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
        self::assertEquals(24053, $file->getFileSize());
        self::assertEquals('image1.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());

        $expectedDateTime = new DateTimeImmutable();
        self::assertTrue(
            $expectedDateTime
                ->diff($file->getCreatedAt())
                ->format('%s')
            < 5
        );
    }

    public function testSimpleUploadFromUrl()
    {
        $url = 'file:///'.__DIR__.'/files/image1.jpg';

        self::assertEquals(File::class, $this->manager->getClass());
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject($url));
        $file->setId(1);

        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
        self::assertEquals(24053, $file->getFileSize());
        self::assertEquals('image1.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());

        $this->manager->moveFile($file);

        self::assertTrue($this->filesystem->fileExists('1'));
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', md5($this->filesystem->read('1')));
        self::assertEquals(24053, strlen($this->filesystem->read('1')));
    }

    public function testUploadedFileUpload()
    {
        $uploadedFile = new UploadedFile(__DIR__.'/files/image1.jpg', 'image_1_uploaded.jpg', 'image/jpg');
        $file = $this->manager->upload($uploadedFile);

        self::assertTrue($file instanceof File);
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
        self::assertEquals(24053, $file->getFileSize());
        self::assertEquals('image_1_uploaded.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testAlreadyUploadedFile()
    {
        $file = new File('image2.jpg', 24053, '9aa1c5fc7c9388166d7ce7fd46648dd1', 'image/jpeg');

        $repository = $this->createMock(Repository::class);
        $repository->expects($this->once())->method('findByHashAndSize')->with(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            24053
        )->willReturn($file);

        $manager = new Manager(
            File::class,
            $this->filesystem,
            $this->createMock(NamingStrategy::class),
            $repository,
        );

        $actual = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));

        self::assertInstanceOf(File::class, $actual);
        self::assertSame($file, $actual);
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
        self::assertEquals(24053, $file->getFileSize());
        self::assertEquals('image2.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testAlreadyUploadedFileWithoutRepository()
    {
        $manager = new Manager(
            File::class,
            $this->filesystem,
            $this->createMock(NamingStrategy::class),
        );

        $file1 = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $file2 = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));

        self::assertInstanceOf(File::class, $file1);
        self::assertInstanceOf(File::class, $file2);
        self::assertNotSame($file1, $file2);
    }

    public function testCreateDirectoryCalled()
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->once())->method('createDirectory')->with('directory');

        $manager = new Manager(
            File::class,
            $filesystem,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return 'directory';
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return 'file';
                }
            },
            new FileRepository(),
        );

        $upload = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $manager->moveFile($upload);
    }

    public function testCreateDirectoryNotCalled()
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects($this->never())->method('createDirectory');

        $manager = new Manager(
            File::class,
            $filesystem,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return null;
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return 'file';
                }
            },
            new FileRepository(),
        );

        $upload = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $manager->moveFile($upload);
    }

    public function testNamingStrategyWithDirectory()
    {
        $manager = new Manager(
            File::class,
            $this->filesystem,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return 'directory/test/';
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return 'directory_test.jpg';
                }
            },
            new FileRepository(),
        );

        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        assert($file instanceof File);
        $file->setId(1);

        $manager->moveFile($file);

        self::assertTrue($this->filesystem->fileExists('directory/test/directory_test.jpg'));
    }


    public function testNamingStrategyWithoutDirectory()
    {
        $manager = new Manager(
            File::class,
            $this->filesystem,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return null;
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return 'directory_test.jpg';
                }
            },
            new FileRepository(),
        );

        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        assert($file instanceof File);
        $file->setId(1);

        $manager->moveFile($file);

        self::assertTrue($this->filesystem->fileExists('directory_test.jpg'));
    }

    public function testMoveDeletedFile()
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $tmpFile = tempnam(sys_get_temp_dir(), 'arxy_files');
        copy($forUpload, $tmpFile);

        $file = $this->manager->upload(new SplFileObject($tmpFile));
        assert($file instanceof File);
        $file->setId(1);
        unlink($tmpFile);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to open '.$tmpFile);
        $this->manager->moveFile($file);
    }

    public function testWrongFileMove()
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('File '.spl_object_id($file).' not found in map');

        $this->manager->moveFile($file);
    }

    public function testWrongFileMoveStringable()
    {
        $file = new StringableFile('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file->setId(25);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('File 25 not found in map');

        $this->manager->moveFile($file);
    }

    public function testSimpleMoveFile()
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject($forUpload));

        self::assertTrue($file instanceof File);

        $file->setId(1);

        $this->manager->moveFile($file);

        self::assertTrue($this->filesystem->fileExists('1'));
        self::assertEquals(md5_file($forUpload), md5($this->filesystem->read('1')));
    }

    public function testSimpleDelete()
    {
        self::assertFalse($this->filesystem->fileExists('2'));

        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        $file->setId(2);

        $this->manager->moveFile($file);
        self::assertTrue($this->filesystem->fileExists('2'));

        $this->manager->remove($file);
        self::assertFalse($this->filesystem->fileExists('2'));
    }

    public function testTemporaryFilePathname()
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        $file->setId(3);

        $pathname = $this->manager->getPathname($file);

        self::assertEquals(__DIR__.'/files/image1.jpg', $pathname);
    }

    public function testFinalFilePathname()
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        $file->setId(3);

        $pathname = $this->manager->getPathname($file);

        self::assertEquals(__DIR__.'/files/image1.jpg', $pathname);

        $this->manager->moveFile($file);

        $pathname = $this->manager->getPathname($file);

        self::assertEquals('3', $pathname);
    }

    public function testTemporaryFileRead()
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        self::assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), $this->manager->read($file));
    }

    public function testFinalFileRead()
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        $file->setId(4);

        $this->manager->moveFile($file);
        self::assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), $this->manager->read($file));
    }

    public function testTemporaryReadStream()
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);

        $stream = $this->manager->readStream($file);
        self::assertIsResource($stream);
        self::assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), stream_get_contents($stream));
    }

    public function testFinalFileReadStream()
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        $file->setId(5);

        $this->manager->moveFile($file);
        $stream = $this->manager->readStream($file);
        self::assertIsResource($stream);
        self::assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), stream_get_contents($stream));
    }

    public function testRefresh()
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $replacement = __DIR__.'/files/image2.jpg';

        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject($forUpload));
        self::assertTrue($file instanceof File);
        $file->setId(6);

        $this->manager->moveFile($file);

        self::assertTrue($this->filesystem->fileExists('6'));
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', md5($this->filesystem->read('6')));
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
        self::assertEquals(24053, $file->getFileSize());
        self::assertEquals('image1.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());

        $this->filesystem->writeStream('6', fopen($replacement, 'r'));

        $this->manager->refresh($file);

        self::assertEquals('59aeac36ae75786be1b573baad0e77c0', $file->getMd5Hash());
        self::assertEquals(22518, $file->getFileSize());
        self::assertEquals('image1.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testRefreshFileMap()
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $replacement = __DIR__.'/files/image2.jpg';
        $tmpFile = tempnam(sys_get_temp_dir(), 'arxy_files');

        copy($forUpload, $tmpFile);

        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject($tmpFile));
        self::assertTrue($file instanceof File);
        $file->setId(6);

        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
//        self::assertEquals(24053, $file->getFileSize());
        self::assertEquals('image/jpeg', $file->getMimeType());

        copy($replacement, $tmpFile);
        $this->manager->refresh($file);

        self::assertEquals('59aeac36ae75786be1b573baad0e77c0', $file->getMd5Hash());
//        self::assertEquals(22518, $file->getFileSize());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testRefreshDeletedFile()
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $tmpFile = tempnam(sys_get_temp_dir(), 'arxy_files');
        copy($forUpload, $tmpFile);

        $file = $this->manager->upload(new SplFileObject($tmpFile));

        unlink($tmpFile);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to detect mimeType for '.$tmpFile);
        $this->manager->refresh($file);
    }

    public function testDeleteDeletedFile(): void
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $file = $this->manager->upload(new SplFileObject($forUpload));
        $this->manager->moveFile($file);

        $this->filesystem->delete($this->manager->getPathname($file));

        $this->manager->remove($file);

        $this->expectNotToPerformAssertions();
    }

    public function testMigrateStrategy(): void
    {
        $forUpload = __DIR__.'/files/image1.jpg';

        $oldStrategy = new class implements NamingStrategy {
            public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
            {
                return null;
            }

            public function getFileName(\Arxy\FilesBundle\Model\File $file): string
            {
                return (string)$file->getId();
            }
        };

        $manager = new Manager(
            File::class,
            $this->filesystem,
            $oldStrategy,
            new FileRepository(),
        );

        /** @var File $file */
        $file = $manager->upload(new SplFileObject($forUpload));
        self::assertTrue($file instanceof File);
        $file->setId(7);

        $manager->moveFile($file);

        self::assertTrue($this->filesystem->fileExists('7'));
        self::assertFalse($this->filesystem->fileExists('test_migrate_7'));


        $manager = new Manager(
            File::class,
            $this->filesystem,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return null;
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return 'test_migrate_'.$file->getId();
                }
            },
            new FileRepository(),
        );

        self::assertTrue($manager->migrate($file, $oldStrategy));

        self::assertFalse($this->filesystem->fileExists('7'));
        self::assertTrue($this->filesystem->fileExists('test_migrate_7'));


        self::assertFalse($manager->migrate($file, $oldStrategy));
    }

    public function testClear()
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        assert($file instanceof File);
        $file->setId(1);
        $this->manager->clear();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File '.spl_object_id($file).' not found in map');

        $this->manager->moveFile($file);
    }

    public function testAnotherMimeTypeDetector()
    {
        $mimeTypeDetector = $this->createMock(MimeTypeDetector::class);
        $mimeTypeDetector->expects($this->exactly(2))
            ->method('detectMimeTypeFromFile')
            ->withConsecutive(
                [$this->identicalTo(__DIR__.'/files/image1.jpg')],
                [$this->identicalTo(__DIR__.'/files/image1.jpg')]
            )
            ->willReturn('image/jpeg');

        $manager = new Manager(
            File::class,
            $this->createMock(FilesystemOperator::class),
            $this->createMock(NamingStrategy::class),
            new FileRepository(),
            $mimeTypeDetector
        );

        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));

        self::assertSame('image/jpeg', $file->getMimeType());
        $manager->refresh($file);
        self::assertSame('image/jpeg', $file->getMimeType());
    }
}
