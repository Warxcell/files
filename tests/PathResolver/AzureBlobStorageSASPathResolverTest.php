<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use PHPUnit\Framework\TestCase;

class AzureBlobStorageSASPathResolverTest extends TestCase
{
    private BlobRestProxy $blobRestProxy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blobRestProxy = $this->createMock(BlobRestProxy::class);
    }

    public function testGetPath()
    {
        $file = new File();

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
                new \DateTime('2021-03-25 23:00:00'),
                new \DateTime('2021-03-25 21:00:00'),
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

    public function testGetPathNullParams()
    {
        $file = new File();

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
                new \DateTime('2021-03-25 23:00:00'),
                null,
                null,
                'https',
                null,
                null,
                null,
                null,
                null,
                null,
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
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null
                    );
                }
            }
        );
        $this->assertSame('url?sas', $sasResolver->getPath($file));
    }
}
