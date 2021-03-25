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
        $file = new File();

        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('pathname');

        $this->blobRestProxy->expects($this->once())
            ->method('getBlobUrl')
            ->with('azure-container', 'pathname')
            ->willReturn('all good');

        $pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->manager,
            $this->blobRestProxy,
            'azure-container'
        );
        $this->assertSame('all good', $pathResolver->getPath($file));
    }

    public function testGetContainer()
    {
        $pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->manager,
            $this->blobRestProxy,
            'azure-container'
        );

        $this->assertSame('azure-container', $pathResolver->getContainer());
    }

    public function testGetBlob()
    {
        $file = new File();

        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('all good');

        $pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->manager,
            $this->blobRestProxy,
            'azure-container'
        );

        $this->assertSame('all good', $pathResolver->getBlob($file));
    }
}
