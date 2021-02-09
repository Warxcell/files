<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Aws\S3\S3ClientInterface;

class AwsS3PathResolver implements PathResolver
{
    /** @var S3ClientInterface */
    private $s3Client;
    /** @var string */
    private $bucket;
    /** @var ManagerInterface */
    private $manager;

    public function __construct(S3ClientInterface $s3Client, string $bucket, ManagerInterface $manager)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
        $this->manager = $manager;
    }

    public function getPath(File $file): string
    {
        return $this->s3Client->getObjectUrl($this->bucket, $this->manager->getPathname($file));
    }
}