<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use DateTimeImmutable;

final class AzureBlobStorageSASParameters
{
    private DateTimeImmutable $expiry;
    private ?DateTimeImmutable $start;
    private ?string $ip;
    private ?string $identifier;
    private ?string $cacheControl;
    private ?string $contentDisposition;
    private ?string $contentEncoding;
    private ?string $contentLanguage;
    private ?string $contentType;

    public function __construct(
        DateTimeImmutable $expiry,
        ?DateTimeImmutable $start = null,
        ?string $ip = null,
        ?string $identifier = null,
        ?string $cacheControl = null,
        ?string $contentDisposition = null,
        ?string $contentEncoding = null,
        ?string $contentLanguage = null,
        ?string $contentType = null
    ) {
        $this->expiry = $expiry;
        $this->start = $start;
        $this->ip = $ip;
        $this->identifier = $identifier;
        $this->cacheControl = $cacheControl;
        $this->contentDisposition = $contentDisposition;
        $this->contentEncoding = $contentEncoding;
        $this->contentLanguage = $contentLanguage;
        $this->contentType = $contentType;
    }

    public function withExpiry(DateTimeImmutable $dateTime): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->expiry = $dateTime;

        return $new;
    }

    public function getExpiry(): DateTimeImmutable
    {
        return $this->expiry;
    }

    public function withStart(DateTimeImmutable $dateTime): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->start = $dateTime;

        return $new;
    }

    public function getStart(): ?DateTimeImmutable
    {
        return $this->start;
    }

    public function withIp(string $ip): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->ip = $ip;

        return $new;
    }


    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function withIdentifier(string $identifier): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->identifier = $identifier;

        return $new;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function withCacheControl(string $cacheControl): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->cacheControl = $cacheControl;

        return $new;
    }

    public function getCacheControl(): ?string
    {
        return $this->cacheControl;
    }

    public function withContentDisposition(string $contentDisposition): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->contentDisposition = $contentDisposition;

        return $new;
    }

    public function getContentDisposition(): ?string
    {
        return $this->contentDisposition;
    }

    public function withContentEncoding(string $contentEncoding): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->contentEncoding = $contentEncoding;

        return $new;
    }

    public function getContentEncoding(): ?string
    {
        return $this->contentEncoding;
    }

    public function withContentLanguage(string $contentLanguage): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->contentLanguage = $contentLanguage;

        return $new;
    }

    public function getContentLanguage(): ?string
    {
        return $this->contentLanguage;
    }

    public function withContentType(string $contentType): AzureBlobStorageSASParameters
    {
        $new = clone($this);
        $new->contentType = $contentType;

        return $new;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }
}
