<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use DateInterval;
use GuzzleHttp\Promise\Create;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AwsPreSignedS3PathResolverTest extends TestCase
{
    /** @var ManagerInterface & MockObject */
    private ManagerInterface $manager;
    private S3ClientInterface $s3Client;
    private PathResolver\AwsS3PreSignedPathResolver $pathResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->s3Client = new S3Client([
            'region' => 'us-west-2',
            'version' => '2006-03-01',
            'credentials' => function () {
                return Create::promiseFor(
                    new Credentials('key', 'secret')
                );
            },
        ]);

        $this->pathResolver = new PathResolver\AwsS3PreSignedPathResolver(
            $this->s3Client,
            'bucket',
            $this->manager,
            new DateInterval(
                'P1D'
            )
        );
    }

    public function testGetPath(): void
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('pathname');
        self::assertStringContainsString(
            'https://bucket.s3.us-west-2.amazonaws.com/pathname',
            $this->pathResolver->getPath($file)
        );
    }
}
