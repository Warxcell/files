<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Aws\S3\S3ClientInterface;
use DateInterval;
use DateTimeImmutable;

class AwsS3PreSignedPathResolver implements PathResolver
{
    private S3ClientInterface $s3Client;
    private string $bucket;
    private ManagerInterface $manager;
    private DateInterval $expiry;

    public function __construct(
        S3ClientInterface $s3Client,
        string $bucket,
        ManagerInterface $manager,
        DateInterval $expiry
    ) {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
        $this->manager = $manager;
        $this->expiry = $expiry;
    }

    public function getPath(File $file): string
    {
        $cmd = $this->s3Client->getCommand(
            'GetObject',
            [
                'Bucket' => $this->bucket,
                'Key' => $this->manager->getPathname($file),
            ]
        );

        $now = new DateTimeImmutable();
        $request = $this->s3Client->createPresignedRequest($cmd, $now->add($this->expiry));

        return (string)$request->getUri();
    }
}
