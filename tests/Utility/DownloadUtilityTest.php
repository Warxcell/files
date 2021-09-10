<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Utility;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Storage\FlysystemStorage;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Tests\MutableFile;
use Arxy\FilesBundle\Utility\DownloadableFile;
use Arxy\FilesBundle\Utility\DownloadUtility;
use DateTimeImmutable;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadUtilityTest extends TestCase
{
    private ManagerInterface $manager;
    private DownloadUtility $downloadUtility;
    private string $pathname;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pathname = __DIR__ . '/../files/image1.jpg';
        $flysystem = $this->createMock(FilesystemOperator::class);
        $flysystem->method('readStream')->willReturn(fopen($this->pathname, 'r'));

        $namingStrategy = $this->createMock(NamingStrategy::class);
        $this->manager = new Manager(File::class, new FlysystemStorage($flysystem), $namingStrategy);
        $this->downloadUtility = new DownloadUtility($this->manager);
    }

    public function createResponseProvider(): iterable
    {
        $file = new File('image1.jpg', 1234, '12345', 'image/jpeg');
        $expiresAt = new DateTimeImmutable('+30 days');
        yield [
            $file,
            'attachment; filename=image1.jpg',
            'image/jpeg',
            $expiresAt->format('D, d M Y H:i'),
            $file->getCreatedAt()->format('D, d M Y H:i:s') . ' GMT',
            1234,
        ];

        yield [
            new DownloadableFile(
                $file,
                'my_name.jpg',
                false,
                new DateTimeImmutable('2021-04-29 15:00:00')
            ),
            'inline; filename=my_name.jpg',
            'image/jpeg',
            'Thu, 29 Apr 2021 15:00:00 GMT',
            $file->getCreatedAt()->format('D, d M Y H:i:s') . ' GMT',
            1234,
        ];

        $mutableFile = new MutableFile('image1.jpg', 1234, '12345', 'image/jpeg');
        $mutableFile->setModifiedAt(new DateTimeImmutable('2021-04-30 15:00:00'));
        yield [
            new DownloadableFile(
                $mutableFile,
                'my_name.jpg',
                false,
                new DateTimeImmutable('2021-05-29 15:00:00')
            ),
            'inline; filename=my_name.jpg',
            'image/jpeg',
            'Sat, 29 May 2021 15:00:00 GMT',
            $mutableFile->getModifiedAt()->format('D, d M Y H:i:s') . ' GMT',
            1234,
        ];
    }

    /**
     * @dataProvider createResponseProvider
     */
    public function testCreateResponse(
        \Arxy\FilesBundle\Model\File $file,
        string $expectedContentDisposition,
        string $expectedContentType,
        string $expectedExpires,
        string $expectedLastModified,
        int $expectedContentLength
    ) {
        $response = $this->downloadUtility->createResponse($file);

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame($expectedContentDisposition, $response->headers->get('Content-Disposition'));
        self::assertSame($expectedContentType, $response->headers->get('Content-Type'));
        self::assertStringContainsString($expectedExpires, $response->headers->get('Expires'));
        self::assertSame($expectedLastModified, $response->headers->get('Last-Modified'));
        self::assertSame((string)$expectedContentLength, $response->headers->get('Content-Length'));

        ob_start();
        $response->sendContent();
        $streamedContent = ob_get_clean();

        self::assertSame(file_get_contents($this->pathname), $streamedContent);
    }
}
