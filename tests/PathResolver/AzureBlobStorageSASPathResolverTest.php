<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Tests\FileRepository;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use PHPUnit\Framework\TestCase;

class AzureBlobStorageSASPathResolverTest extends TestCase
{
    private ManagerInterface $manager;
    private BlobRestProxy $blobRestProxy;

    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->manager = new Manager(
            File::class,
            new FileRepository(),
            $filesystem,
            new class implements NamingStrategy {
                public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
                {
                    return 'directory/';
                }

                public function getFileName(\Arxy\FilesBundle\Model\File $file): string
                {
                    return (string)$file->getId();
                }
            }
        );

        $this->blobRestProxy = $this->createMock(BlobRestProxy::class);

    }

    public function testGetPath()
    {
        $file = new File();
        $file->setId(5);

        $pathResolver = $this->createMock(PathResolver\AzureBlobStoragePathResolver::class);
        $pathResolver->expects($this->once())->method('getPath')->with($file)->willReturn('url');
        $pathResolver->expects($this->once())->method('getContainer')->willReturn('azure-container');
        $pathResolver->expects($this->once())->method('getBlob')->willReturn('azure-blob');

        $sasHelper = $this->createMock(BlobSharedAccessSignatureHelper::class);
        $sasHelper->expects($this->once())
            ->method('generateBlobServiceSharedAccessSignatureToken')
            ->with(
                Resources::RESOURCE_TYPE_BLOB,
                'azure-container/azure-blob',
                'r',
                new \DateTimeImmutable('2021-03-25 23:00:00'),
                new \DateTimeImmutable('2021-03-25 21:00:00'),
                '127.0.0.1',
                'https',
                'identifier',
                'cache-control',
                'content-disposition',
                'content-encoding',
                'content-language',
                'content-type',
            )
            ->willReturn('sas');

        $sasResolver = new PathResolver\AzureBlobStorageSASPathResolver(
            $pathResolver,
            $sasHelper,
            new class implements PathResolver\AzureBlobStorageSASParametersFactory {
                public function create(\Arxy\FilesBundle\Model\File $file): PathResolver\AzureBlobStorageSASParameters
                {
                    return new PathResolver\AzureBlobStorageSASParameters(
                        new \DateTimeImmutable('2021-03-25 23:00:00'),
                        new \DateTimeImmutable('2021-03-25 21:00:00'),
                        '127.0.0.1',
                        'identifier',
                        'cache-control',
                        'content-disposition',
                        'content-encoding',
                        'content-language',
                        'content-type',
                    );
                }
            }
        );
        $this->assertSame('url?sas', $sasResolver->getPath($file));
    }
}
