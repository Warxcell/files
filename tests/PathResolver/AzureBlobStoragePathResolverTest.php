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
use PHPUnit\Framework\TestCase;

class AzureBlobStoragePathResolverTest extends TestCase
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

        $this->blobRestProxy->expects($this->once())
            ->method('getBlobUrl')
            ->with('azure-container', $this->manager->getPathname($file))
            ->willReturn(
                'all good'
            );

        $pathResolver = new PathResolver\AzureBlobStoragePathResolver(
            $this->manager,
            $this->blobRestProxy,
            'azure-container'
        );
        $this->assertSame('all good', $pathResolver->getPath($file));
    }
}
