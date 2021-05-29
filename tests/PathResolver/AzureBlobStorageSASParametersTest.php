<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\PathResolver;

use Arxy\FilesBundle\PathResolver;
use PHPUnit\Framework\TestCase;

class AzureBlobStorageSASParametersTest extends TestCase
{
    private PathResolver\AzureBlobStorageSASParameters $parameters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parameters = new PathResolver\AzureBlobStorageSASParameters(new \DateTimeImmutable());
    }

    public function testWithExpiry(): void
    {
        $expiry = new \DateTimeImmutable();

        $new = $this->parameters->withExpiry($expiry);

        self::assertNotSame($new, $this->parameters);
        self::assertSame($expiry, $new->getExpiry());
    }

    public function testWithStart(): void
    {
        $expiry = new \DateTimeImmutable();

        $new = $this->parameters->withStart($expiry);

        self::assertNotSame($new, $this->parameters);
        self::assertSame($expiry, $new->getStart());
    }

    public function testWithIp(): void
    {
        $new = $this->parameters->withIp('127.0.0.1');

        self::assertNotSame($new, $this->parameters);
        self::assertSame('127.0.0.1', $new->getIp());
    }

    public function testWithIdentifier(): void
    {
        $new = $this->parameters->withIdentifier('id');

        self::assertNotSame($new, $this->parameters);
        self::assertSame('id', $new->getIdentifier());
    }

    public function testWithCacheControl(): void
    {
        $new = $this->parameters->withCacheControl('cache-control');

        self::assertNotSame($new, $this->parameters);
        self::assertSame('cache-control', $new->getCacheControl());
    }

    public function testWithContentDisposition(): void
    {
        $new = $this->parameters->withContentDisposition('content-disposition');

        self::assertNotSame($new, $this->parameters);
        self::assertSame('content-disposition', $new->getContentDisposition());
    }

    public function testWithContentEncoding(): void
    {
        $new = $this->parameters->withContentEncoding('content-encoding');

        self::assertNotSame($new, $this->parameters);
        self::assertSame('content-encoding', $new->getContentEncoding());
    }

    public function testWithContentLanguage(): void
    {
        $new = $this->parameters->withContentLanguage('content-language');

        self::assertNotSame($new, $this->parameters);
        self::assertSame('content-language', $new->getContentLanguage());
    }

    public function testWithContentType(): void
    {
        $new = $this->parameters->withContentType('content-type');

        self::assertNotSame($new, $this->parameters);
        self::assertSame('content-type', $new->getContentType());
    }
}
