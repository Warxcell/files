<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Storage\FlysystemStorage;
use Arxy\FilesBundle\Utility\FileDownloader;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

class FileDownloaderTest extends TestCase
{
    private ManagerInterface $manager;
    private FilesystemOperator $filesystem;

    private FileDownloader $downloader;

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

        $this->downloader = new FileDownloader($this->manager);
    }

    public function testDownloadAsSplFilePersisted(): void
    {
        $file = $this->manager->upload(new \SplFileObject(__DIR__ . '/files/image1.jpg'));
        $this->manager->moveFile($file);

        $splFile = $this->downloader->downloadAsSplFile($file);

        self::assertFileEquals($splFile->getPathname(), __DIR__ . '/files/image1.jpg');
    }

    public function testDownloadAsSplFileNotPersisted(): void
    {
        $file = $this->manager->upload(new \SplFileObject(__DIR__ . '/files/image1.jpg'));

        $splFile = $this->downloader->downloadAsSplFile($file);

        self::assertFileEquals($splFile->getPathname(), __DIR__ . '/files/image1.jpg');
    }
}