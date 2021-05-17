<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PHPUnit\Framework\TestCase;

class AzureBlobStoragePathResolverTest extends TestCase
{
    private ManagerInterface $manager;
    private BlobRestProxy $blobRestProxy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->blobRestProxy = $this->createMock(BlobRestProxy::class);
    }

    public function testGetPath()
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('pathname');

        $this->blobRestProxy->expects($this->once())
            ->method('getBlobUrl')
            ->with('azure-container', 'pathname')
            ->willReturn('all good');

        $pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->blobRestProxy,
            'azure-container',
            $this->manager,
        );
        self::assertSame('all good', $pathResolver->getPath($file));
    }

    public function testGetContainer()
    {
        $pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->blobRestProxy,
            'azure-container',
            $this->manager,
        );

        self::assertSame('azure-container', $pathResolver->getContainer());
    }

    public function testGetBlob()
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('all good');

        $pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->blobRestProxy,
            'azure-container',
            $this->manager,
        );

        self::assertSame('all good', $pathResolver->getBlob($file));
    }
}
