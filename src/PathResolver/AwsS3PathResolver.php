<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Aws\S3\S3ClientInterface;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class AwsS3PathResolver implements PathResolver
{
    /**
     * @param ManagerInterface<T, mixed> $manager
     */
    public function __construct(
        private readonly S3ClientInterface $s3Client,
        private readonly string $bucket,
        private readonly ManagerInterface $manager
    ) {

    }

    #[\Override]
    public function getPath(File $file): string
    {
        return $this->s3Client->getObjectUrl($this->bucket, $this->manager->getPathname($file));
    }
}
