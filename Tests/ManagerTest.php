<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    private ManagerInterface $manager;
    private FilesystemOperator $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());

        $this->manager = new Manager(
            File::class, new FileRepository(), $this->filesystem, new class implements NamingStrategy {
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
    }

    public function testSimpleUpload()
    {
        $this->assertEquals(File::class, $this->manager->getClass());
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));

        $this->assertTrue($file instanceof File);
        $this->assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
        $this->assertEquals(24053, $file->getFileSize());
        $this->assertEquals('image1.jpg', $file->getOriginalFilename());
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testWrongFileMove()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage('File not found in map');

        $file = new File();
        $file->setId(25);

        $this->manager->moveFile($file);
    }

    public function testSimpleMoveFile()
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject($forUpload));

        $this->assertTrue($file instanceof File);

        $file->setId(1);

        $this->manager->moveFile($file);

        $this->assertTrue($this->filesystem->fileExists('1'));
        $this->assertEquals(md5_file($forUpload), md5($this->filesystem->read('1')));
    }

    public function testSimpleDelete()
    {
        $this->assertFalse($this->filesystem->fileExists('2'));

        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));
        $this->assertTrue($file instanceof File);
        $file->setId(2);

        $this->manager->moveFile($file);
        $this->assertTrue($this->filesystem->fileExists('2'));

        $this->manager->remove($file);
        $this->assertFalse($this->filesystem->fileExists('2'));
    }

    public function testTemporaryFilePathname()
    {
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));
        $this->assertTrue($file instanceof File);
        $file->setId(3);

        $pathname = $this->manager->getPathname($file);

        $this->assertEquals(__DIR__.'/files/image1.jpg', $pathname);
    }

    public function testFinalFilePathname()
    {
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));
        $this->assertTrue($file instanceof File);
        $file->setId(3);

        $pathname = $this->manager->getPathname($file);

        $this->assertEquals(__DIR__.'/files/image1.jpg', $pathname);

        $this->manager->moveFile($file);

        $pathname = $this->manager->getPathname($file);

        $this->assertEquals('3', $pathname);
    }

    public function testTemporaryFileRead()
    {
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));
        $this->assertTrue($file instanceof File);
        $this->assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), $this->manager->read($file));
    }

    public function testFinalFileRead()
    {
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));
        $this->assertTrue($file instanceof File);
        $file->setId(4);

        $this->manager->moveFile($file);
        $this->assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), $this->manager->read($file));
    }

    public function testTemporaryReadStream()
    {
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));
        $this->assertTrue($file instanceof File);

        $stream = $this->manager->readStream($file);
        $this->assertIsResource($stream);
        $this->assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), stream_get_contents($stream));
    }

    public function testFinalFileReadStream()
    {
        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject(__DIR__.'/files/image1.jpg'));
        $this->assertTrue($file instanceof File);
        $file->setId(5);

        $this->manager->moveFile($file);
        $stream = $this->manager->readStream($file);
        $this->assertIsResource($stream);
        $this->assertEquals(file_get_contents(__DIR__.'/files/image1.jpg'), stream_get_contents($stream));
    }

    public function testRefresh()
    {
        $forUpload = __DIR__.'/files/image1.jpg';
        $replacement = __DIR__.'/files/image2.jpg';

        /** @var File $file */
        $file = $this->manager->upload(new \SplFileObject($forUpload));
        $this->assertTrue($file instanceof File);
        $file->setId(6);

        $this->manager->moveFile($file);

        $this->assertEquals(md5_file($forUpload), md5($this->filesystem->read('6')));
        $this->assertEquals('9aa1c5fc7c9388166d7ce7fd46648dd1', $file->getMd5Hash());
        $this->assertEquals(24053, $file->getFileSize());
        $this->assertEquals('image1.jpg', $file->getOriginalFilename());
        $this->assertEquals('image/jpeg', $file->getMimeType());

        $this->filesystem->writeStream('6', fopen($replacement, 'r'));

        $this->manager->refresh($file);

        $this->assertEquals('59aeac36ae75786be1b573baad0e77c0', $file->getMd5Hash());
        //$this->assertEquals(22518, $file->getFileSize());
        $this->assertEquals('image1.jpg', $file->getOriginalFilename());
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }
}
