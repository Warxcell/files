<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AzureBlobStoragePathResolver implements PathResolver
{
    private BlobRestProxy $client;
    private string $container;
    private ManagerInterface $manager;

    public function __construct(BlobRestProxy $client, string $container, ManagerInterface $manager)
    {
        $this->client = $client;
        $this->container = $container;
        $this->manager = $manager;
    }

    public function getPath(File $file): string
    {
        return $this->client->getBlobUrl($this->getContainer(), $this->getBlob($file));
    }

    public function getContainer(): string
    {
        return $this->container;
    }

    public function getBlob(File $file): string
    {
        return $this->manager->getPathname($file);
    }
}
