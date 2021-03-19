<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Tests\FileRepository;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class AssetsPathResolverTest extends TestCase
{
    private PathResolver\AssetsPathResolver $pathResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $manager = new Manager(
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

        $this->pathResolver = new PathResolver\AssetsPathResolver(
            $manager,
            new Packages(
                new PathPackage(
                    '/media',
                    new EmptyVersionStrategy()
                )
            )
        );
    }

    public function testGetPath()
    {
        $file = new File();
        $file->setId(5);

        $this->assertSame('/media/directory/5', $this->pathResolver->getPath($file));
    }
}
