<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Aws\CloudFront\UrlSigner;
use DateInterval;
use DateTimeImmutable;

use function rtrim;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class AwsCloudFrontPreSignedPathResolver implements PathResolver
{
    /**
     * @param ManagerInterface<T, mixed> $manager
     */
    public function __construct(
        private readonly UrlSigner $urlSigner,
        private readonly string $url,
        private readonly ManagerInterface $manager,
        private readonly DateInterval $expiry,
    ) {
    }

    #[\Override]
    public function getPath(File $file): string
    {
        $url = rtrim($this->url, '/') . '/' . $this->manager->getPathname($file);

        $now = new DateTimeImmutable();

        /* @phpstan-ignore missingType.checkedException (url is valid) */
        return $this->urlSigner->getSignedUrl($url, $now->add($this->expiry)->getTimestamp());
    }
}
