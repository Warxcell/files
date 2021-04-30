<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Utility;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Utility\DownloadableFile;
use Arxy\FilesBundle\Utility\DownloadUtility;
use DateTimeImmutable;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadUtilityTest extends TestCase
{
    private ManagerInterface $manager;
    private DownloadUtility $downloadUtility;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = new class implements Repository {
            public function findByHashAndSize(string $hash, int $size): ?File
            {
                return null;
            }

            public function findAllForBatchProcessing(): iterable
            {
                return [];
            }
        };
        $flysystem = new Filesystem(new InMemoryFilesystemAdapter());
        $namingStrategy = new NamingStrategy\SplitHashStrategy();
        $this->manager = new Manager(File::class, $flysystem, $namingStrategy, $repository);
        $this->downloadUtility = new DownloadUtility($this->manager);
    }

    public function testCreateResponse()
    {
        $pathname = __DIR__.'/../files/image1.jpg';
        $file = $this->manager->upload(new \SplFileObject($pathname));
        $this->manager->moveFile($file);

        $response = $this->downloadUtility->createResponse($file);

        $now = new DateTimeImmutable('+30 days');
        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('attachment; filename=image1.jpg', $response->headers->get('Content-Disposition'));
        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
        self::assertSame($now->format('D, d M Y H:i:s').' GMT', $response->headers->get('Expires'));

        ob_start();
        $response->sendContent();
        $streamedContent = ob_get_clean();

        self::assertSame(file_get_contents($pathname), $streamedContent);
    }

    public function testCreateResponseDownloadableFile()
    {
        $pathname = __DIR__.'/../files/image1.jpg';
        $file = $this->manager->upload(new \SplFileObject($pathname));
        $this->manager->moveFile($file);

        $response = $this->downloadUtility->createResponse(
            new DownloadableFile($file, 'my_name.jpg', false, new \DateTimeImmutable('2021-04-29 15:00:00'))
        );

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('inline; filename=my_name.jpg', $response->headers->get('Content-Disposition'));
        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
        self::assertSame('Thu, 29 Apr 2021 15:00:00 GMT', $response->headers->get('Expires'));

        ob_start();
        $response->sendContent();
        $streamedContent = ob_get_clean();

        self::assertSame(file_get_contents($pathname), $streamedContent);
    }
}
