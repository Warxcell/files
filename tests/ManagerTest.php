<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Event\PostMove;
use Arxy\FilesBundle\Event\PostUpdate;
use Arxy\FilesBundle\Event\PostUpload;
use Arxy\FilesBundle\Event\PreMove;
use Arxy\FilesBundle\Event\PreRemove;
use Arxy\FilesBundle\Event\PreUpdate;
use Arxy\FilesBundle\FileException;
use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\MutableFile;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Storage;
use Arxy\FilesBundle\Storage\FlysystemStorage;
use Arxy\FilesBundle\UnableToUpload;
use DateTimeImmutable;
use ErrorException;
use Exception;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\MimeTypeDetection\MimeTypeDetector;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use SplFileInfo;
use SplFileObject;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;
use function sprintf;

class ManagerTest extends TestCase
{
    private ManagerInterface $manager;
    private FilesystemOperator $filesystem;

    public function testNotSupportedHashAlgorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The algorithm "not_existing" is not supported.');

        $manager = new Manager(
            File::class,
            $this->createMock(Storage::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            null,
            null,
            'not_existing'
        );
    }

    public function testFailedToDetermineMimeType(): void
    {
        $manager = new Manager(
            File::class,
            $this->createMock(Storage::class),
            $this->createMock(NamingStrategy::class),
            $this->createMock(Repository::class),
            $this->createMock(MimeTypeDetector::class)
        );

        try {
            $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        } catch (UnableToUpload $exception) {
            self::assertNotNull($exception->getPrevious());
            self::assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
            self::assertStringContainsString('Failed to detect mimeType for ', $exception->getPrevious()->getMessage());
        }
    }

    public function testUploadEvent(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $manager = new Manager(
            File::class,
            $this->createMock(Storage::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            $dispatcher
        );

        $dispatcher->expects(self::once())->method('dispatch')->with(
            self::callback(
                static fn (PostUpload $fileUploaded): bool => $fileUploaded->getFile() instanceof File
                    && $fileUploaded->getManager() === $manager
            )
        );

        $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
    }

    public function testMoveEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $manager = new Manager(
            File::class,
            $this->createMock(Storage::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            $dispatcher
        );

        $dispatcher->expects(self::exactly(3))->method('dispatch')->withConsecutive(
            [
                self::callback(
                    static fn (PostUpload $fileUploaded): bool => $fileUploaded->getFile() instanceof File
                        && $fileUploaded->getManager() === $manager
                ),
            ],
            [
                self::callback(
                    static fn (PreMove $preRemove): bool => $preRemove->getFile() instanceof File
                        && $preRemove->getManager() === $manager
                ),
            ],
            [
                self::callback(
                    static fn (PostMove $preRemove): bool => $preRemove->getFile() instanceof File
                        && $preRemove->getManager() === $manager
                ),
            ]
        );

        /** @var File $file */
        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $manager->moveFile($file);
    }

    public function testPreMoveEventNotFired(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $manager = new Manager(
            File::class,
            $this->createMock(Storage::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            $dispatcher
        );

        $dispatcher->expects(self::exactly(0))->method('dispatch')->withConsecutive(
            [
                self::callback(
                    static fn (PostUpload $fileUploaded): bool => $fileUploaded->getFile() instanceof File
                        && $fileUploaded->getManager() === $manager
                ),
            ]
        );

        $file = new File('image2.jpg', 24053, '9aa1c5fc7c9388166d7ce7fd46648dd1', 'image/jpeg');
        try {
            $manager->moveFile($file);
        } catch (Exception $exception) {
        }
    }

    public function testPreRemove(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $manager = new Manager(
            File::class,
            $this->createMock(Storage::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            $dispatcher
        );

        $dispatcher->expects(self::exactly(2))->method('dispatch')->withConsecutive(
            [
                self::callback(
                    static fn (PostUpload $fileUploaded): bool => $fileUploaded->getFile() instanceof File
                        && $fileUploaded->getManager() === $manager
                ),
            ],
            [
                self::callback(
                    static fn (PreRemove $preRemove): bool => $preRemove->getFile() instanceof File
                        && $preRemove->getManager() === $manager
                ),
            ]
        );

        /** @var File $file */
        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $manager->remove($file);
    }

    public function testSimpleUpload(): void
    {
        self::assertEquals(File::class, $this->manager->getClass());
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));

        self::assertTrue($file instanceof File);
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getHash());
        self::assertEquals(24053, $file->getSize());
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

    public function testSimpleUploadFromUrl(): void
    {
        $url = 'file:///'.__DIR__.'/files/image1.jpg';

        self::assertEquals(File::class, $this->manager->getClass());
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject($url));
        $file->setId(1);

        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getHash());
        self::assertEquals(24053, $file->getSize());
        self::assertEquals('image1.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());

        $this->manager->moveFile($file);

        self::assertTrue($this->filesystem->fileExists('1'));
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', md5($this->filesystem->read('1')));
        self::assertEquals(24053, strlen($this->filesystem->read('1')));
    }

    public function testUploadedFileUpload(): void
    {
        $uploadedFile = new UploadedFile(__DIR__.'/files/image1.jpg', 'image_1_uploaded.jpg', 'image/jpg');
        $file = $this->manager->upload($uploadedFile);

        self::assertTrue($file instanceof File);
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getHash());
        self::assertEquals(24053, $file->getSize());
        self::assertEquals('image_1_uploaded.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testAlreadyUploadedFile(): void
    {
        $file = new File('image2.jpg', 24053, '9aa1c5fc7c9388166d7ce7fd46648dd1', 'image/jpeg');

        $repository = $this->createMock(Repository::class);
        $repository->expects(self::once())->method('findByHashAndSize')->with(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            24053
        )->willReturn($file);

        $manager = new Manager(
            File::class,
            new FlysystemStorage($this->filesystem),
            $this->createMock(NamingStrategy::class),
            $repository,
        );

        $actual = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));

        self::assertInstanceOf(File::class, $actual);
        self::assertSame($file, $actual);
        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getHash());
        self::assertEquals(24053, $file->getSize());
        self::assertEquals('image2.jpg', $file->getOriginalFilename());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testAlreadyUploadedFileWithoutRepository(): void
    {
        $manager = new Manager(
            File::class,
            new FlysystemStorage($this->filesystem),
            $this->createMock(NamingStrategy::class),
        );

        $file1 = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $file2 = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));

        self::assertInstanceOf(File::class, $file1);
        self::assertInstanceOf(File::class, $file2);
        self::assertNotSame($file1, $file2);
    }

    public function testNamingStrategyWithDirectory(): void
    {
        $manager = new Manager(
            File::class,
            new FlysystemStorage($this->filesystem),
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

    public function testNamingStrategyWithoutDirectory(): void
    {
        $manager = new Manager(
            File::class,
            new FlysystemStorage($this->filesystem),
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

    public function testMoveDeletedFile(): void
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $tmpFile = tempnam(sys_get_temp_dir(), 'arxy_files');
        copy($forUpload, $tmpFile);

        $file = $this->manager->upload(new SplFileObject($tmpFile));
        assert($file instanceof File);
        $file->setId(1);
        unlink($tmpFile);

        try {
            $this->manager->moveFile($file);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(FileException::class, $exception);
            $this->assertEquals('Unable to move file', $exception->getMessage());

            $this->assertInstanceOf(ErrorException::class, $exception->getPrevious());
            $this->assertEquals(
                sprintf('fopen(%s): Failed to open stream: No such file or directory', $tmpFile),
                $exception->getPrevious()->getMessage()
            );
        }
    }

    public function testWrongFileMove(): void
    {
        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        try {
            $this->manager->moveFile($file);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(OutOfBoundsException::class, $exception);
            $this->assertEquals(
                'File '.(string)spl_object_id($file).' not found in map',
                $exception->getMessage()
            );
        }
    }

    public function testWrongFileMoveStringable(): void
    {
        $file = new StringableFile('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file->setId(25);

        try {
            $this->manager->moveFile($file);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(OutOfBoundsException::class, $exception);
            $this->assertEquals('File 25 not found in map', $exception->getMessage());
        }
    }

    public function testSimpleMoveFile(): void
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

    public function testSimpleDelete(): void
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

    public function testFinalFilePathname(): void
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

    public function testTemporaryFileRead(): void
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        self::assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), $this->manager->read($file));
    }

    public function testFinalFileRead(): void
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);
        $file->setId(4);

        $this->manager->moveFile($file);
        self::assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), $this->manager->read($file));
    }

    public function testTemporaryReadStream(): void
    {
        /** @var File $file */
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        self::assertTrue($file instanceof File);

        $stream = $this->manager->readStream($file);
        self::assertIsResource($stream);
        self::assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), stream_get_contents($stream));
    }

    public function testFinalFileReadStream(): void
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

    public function testDeleteDeletedFile(): void
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $file = $this->manager->upload(new SplFileObject($forUpload));
        $this->manager->moveFile($file);

        $this->filesystem->delete($this->manager->getPathname($file));

        $this->manager->remove($file);

        $this->expectNotToPerformAssertions();
    }

    public function testWrite(): void
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $file = $this->manager->upload(new SplFileObject($forUpload));
        $this->manager->moveFile($file);

        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getHash());
        self::assertEquals(24053, $file->getSize());
        self::assertEquals('image/jpeg', $file->getMimeType());

        assert($file instanceof MutableFile);
        $this->manager->write($file, new SplFileInfo(__DIR__.'/files/image2.jpg'));

        self::assertEquals('59aeac36ae75786be1b573baad0e77c0', $file->getHash());
        self::assertEquals(22518, $file->getSize());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testWriteTemporaryFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'files');
        $forUpload = __DIR__.'/files/image1.jpg';

        copy($forUpload, $tmp);
        $file = $this->manager->upload(new SplFileObject($tmp));

        self::assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getHash());
        self::assertEquals(24053, $file->getSize());
        self::assertEquals('image/jpeg', $file->getMimeType());

        assert($file instanceof MutableFile);
        $this->manager->write($file, new SplFileInfo(__DIR__.'/files/image2.jpg'));

        self::assertEquals('59aeac36ae75786be1b573baad0e77c0', $file->getHash());
        self::assertEquals(22518, $file->getSize());
        self::assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testWriteEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $manager = new Manager(
            File::class,
            $this->createMock(Storage::class),
            $this->createMock(NamingStrategy::class),
            null,
            null,
            null,
            $dispatcher
        );
        /** @var File $file */
        $file = $manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        $manager->moveFile($file);

        $dispatcher->expects(self::exactly(2))->method('dispatch')->withConsecutive(
            [
                self::callback(
                    static fn (PreUpdate $preRemove): bool => $preRemove->getFile() === $file
                        && $preRemove->getManager() === $manager
                ),
            ],
            [
                self::callback(
                    static fn (PostUpdate $postUpdate): bool => $postUpdate->getFile() === $file
                        && $postUpdate->getManager() === $manager
                ),
            ]
        );

        $splTemp = new SplTempFileObject();
        $splTemp->fwrite('test');
        $manager->write($file, $splTemp);
    }

    public function testClear(): void
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/files/image1.jpg'));
        assert($file instanceof File);
        $file->setId(1);
        $this->manager->clear();

        try {
            $this->manager->moveFile($file);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(OutOfBoundsException::class, $exception);
            $this->assertEquals(
                'File '.(string)spl_object_id($file).' not found in map',
                $exception->getMessage()
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $this->manager = new Manager(
            File::class,
            new FlysystemStorage($this->filesystem),
            /** @implements NamingStrategy<File> */
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
}
