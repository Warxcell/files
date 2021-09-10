<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AzureBlobStoragePathResolverTest extends TestCase
{
    /** @var ManagerInterface & MockObject */
    private ManagerInterface $manager;
    /** @var BlobRestProxy & MockObject */
    private BlobRestProxy $blobRestProxy;
    private PathResolver\AzureBlobStoragePathResolver $pathResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->blobRestProxy = $this->createMock(BlobRestProxy::class);
        $this->pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->blobRestProxy,
            'azure-container',
            $this->manager,
        );
    }

    public function testGetPath(): void
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('pathname');

        $this->blobRestProxy->expects($this->once())
            ->method('getBlobUrl')
            ->with('azure-container', 'pathname')
            ->willReturn('all good');

        self::assertSame('all good', $this->pathResolver->getPath($file));
    }

    public function testGetContainer(): void
    {
        self::assertSame('azure-container', $this->pathResolver->getContainer());
    }

    public function testGetBlob(): void
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('all good');

        self::assertSame('all good', $this->pathResolver->getBlob($file));
    }
}
