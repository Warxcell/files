<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AzureBlobStoragePathResolver implements PathResolver
{
    private ManagerInterface $manager;
    private BlobRestProxy $client;
    private string $container;

    public function __construct(ManagerInterface $manager, BlobRestProxy $client, string $container)
    {
        $this->manager = $manager;
        $this->client = $client;
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getBlob(File $file): string
    {
        return $this->manager->getPathname($file);
    }

    public function getPath(File $file): string
    {
        return $this->client->getBlobUrl($this->getContainer(), $this->getBlob($file));
    }
}
