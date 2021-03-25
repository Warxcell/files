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

    public function testWithExpiry()
    {
        $expiry = new \DateTimeImmutable();

        $new = $this->parameters->withExpiry($expiry);

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame($expiry, $new->getExpiry());
    }

    public function testWithStart()
    {
        $expiry = new \DateTimeImmutable();

        $new = $this->parameters->withStart($expiry);

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame($expiry, $new->getStart());
    }

    public function testWithIp()
    {
        $new = $this->parameters->withIp('127.0.0.1');

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame('127.0.0.1', $new->getIp());
    }

    public function testWithIdentifier()
    {
        $new = $this->parameters->withIdentifier('id');

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame('id', $new->getIdentifier());
    }

    public function testWithCacheControl()
    {
        $new = $this->parameters->withCacheControl('cache-control');

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame('cache-control', $new->getCacheControl());
    }

    public function testWithContentDisposition()
    {
        $new = $this->parameters->withContentDisposition('content-disposition');

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame('content-disposition', $new->getContentDisposition());
    }

    public function testWithContentEncoding()
    {
        $new = $this->parameters->withContentEncoding('content-encoding');

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame('content-encoding', $new->getContentEncoding());
    }

    public function testWithContentLanguage()
    {
        $new = $this->parameters->withContentLanguage('content-language');

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame('content-language', $new->getContentLanguage());
    }

    public function testWithContentType()
    {
        $new = $this->parameters->withContentType('content-type');

        $this->assertNotSame($new, $this->parameters);
        $this->assertSame('content-type', $new->getContentType());
    }
}
