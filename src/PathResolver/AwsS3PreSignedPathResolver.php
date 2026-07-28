<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Aws\S3\S3ClientInterface;
use DateInterval;
use DateTimeImmutable;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class AwsS3PreSignedPathResolver implements PathResolver
{
    /**
     * @param ManagerInterface<T, mixed> $manager
     */
    public function __construct(
        private readonly S3ClientInterface $s3Client,
        private readonly string $bucket,
        private readonly ManagerInterface $manager,
        private readonly DateInterval $expiry
    ) {
    }

    #[\Override]
    public function getPath(File $file): string
    {
        /* @phpstan-ignore missingType.checkedException */
        $cmd = $this->s3Client->getCommand(
            'GetObject',
            [
                'Bucket' => $this->bucket,
                'Key' => $this->manager->getPathname($file),
            ]
        );

        $now = new DateTimeImmutable();
        $request = $this->s3Client->createPresignedRequest($cmd, $now->add($this->expiry)->getTimestamp());

        return (string)$request->getUri();
    }
}
