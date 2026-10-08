<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Aws\CloudFront\UrlSigner;
use DateInterval;
use DateTimeImmutable;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class AwsCloudFrontPreSignedPathResolver implements PathResolver
{
    /**
     * @param PathResolver<T> $pathResolver
     */
    public function __construct(
        private readonly PathResolver $pathResolver,
        private readonly UrlSigner $urlSigner,
        private readonly DateInterval $expiry,
    ) {
    }

    #[\Override]
    public function getPath(File $file): string
    {
        $url = $this->pathResolver->getPath($file);

        $now = new DateTimeImmutable();

        /* @phpstan-ignore missingType.checkedException (url is valid) */
        return $this->urlSigner->getSignedUrl($url, $now->add($this->expiry)->getTimestamp());
    }
}
