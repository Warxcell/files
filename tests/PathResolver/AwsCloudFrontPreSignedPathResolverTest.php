<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\Tests\File;
use Aws\CloudFront\UrlSigner;
use DateInterval;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function openssl_pkey_export;
use function openssl_pkey_new;

use const OPENSSL_KEYTYPE_RSA;

class AwsCloudFrontPreSignedPathResolverTest extends TestCase
{
    private ManagerInterface&MockObject $manager;
    private UrlSigner $urlSigner;
    private PathResolver\AwsCloudFrontPreSignedPathResolver $pathResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = $this->createMock(ManagerInterface::class);

        $privateKeyResource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        self::assertNotFalse($privateKeyResource);
        openssl_pkey_export($privateKeyResource, $privateKey);

        $this->urlSigner = new UrlSigner('key-pair-id', $privateKey);

        $this->pathResolver = new PathResolver\AwsCloudFrontPreSignedPathResolver(
            $this->urlSigner,
            'https://d111111abcdef8.cloudfront.net',
            $this->manager,
            new DateInterval('P1D')
        );
    }

    public function testGetPath(): void
    {
        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('pathname');

        $path = $this->pathResolver->getPath($file);

        self::assertStringStartsWith('https://d111111abcdef8.cloudfront.net/pathname?', $path);
        self::assertStringContainsString('Expires=', $path);
        self::assertStringContainsString('Signature=', $path);
        self::assertStringContainsString('Key-Pair-Id=key-pair-id', $path);
    }

    public function testGetPathWithTrailingSlash(): void
    {
        $pathResolver = new PathResolver\AwsCloudFrontPreSignedPathResolver(
            $this->urlSigner,
            'https://d111111abcdef8.cloudfront.net/',
            $this->manager,
            new DateInterval('P1D')
        );

        $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $this->manager->expects($this->once())->method('getPathname')->with($file)->willReturn('pathname');

        $path = $pathResolver->getPath($file);

        self::assertStringStartsWith('https://d111111abcdef8.cloudfront.net/pathname?', $path);
        self::assertStringContainsString('Expires=', $path);
        self::assertStringContainsString('Signature=', $path);
        self::assertStringContainsString('Key-Pair-Id=key-pair-id', $path);
    }
}
