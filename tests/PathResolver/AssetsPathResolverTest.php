<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class AssetsPathResolverTest extends TestCase
{
    private PathResolver\AssetsPathResolver $pathResolver;
    private ManagerInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(ManagerInterface::class);

        $this->pathResolver = new PathResolver\AssetsPathResolver(
            $this->manager,
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
        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('directory/5');
        $this->assertSame('/media/directory/5', $this->pathResolver->getPath($file));
    }
}
