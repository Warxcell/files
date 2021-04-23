<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use Aws\S3\S3ClientInterface;
use PHPUnit\Framework\TestCase;

class AwsS3PathResolverTest extends TestCase
{
    private ManagerInterface $manager;
    private S3ClientInterface $s3Client;
    private PathResolver\AwsS3PathResolver $pathResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->s3Client = $this->createMock(S3ClientInterface::class);

        $this->pathResolver = new PathResolver\AwsS3PathResolver($this->s3Client, 'bucket', $this->manager);
    }

    public function testGetPath()
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('pathname');

        $this->s3Client->expects($this->once())
            ->method('getObjectUrl')
            ->with('bucket', 'pathname')
            ->willReturn('all good');

        $this->assertSame('all good', $this->pathResolver->getPath($file));
    }
}
