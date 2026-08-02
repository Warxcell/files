<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class AzureBlobStoragePathResolver implements PathResolver
{
    /**
     * @param ManagerInterface<T, mixed> $manager
     */
    public function __construct(
        private readonly BlobRestProxy $client,
        private readonly string $container,
        private readonly ManagerInterface $manager
    ) {
    }

    #[\Override]
    public function getPath(File $file): string
    {
        return $this->client->getBlobUrl($this->getContainer(), $this->getBlob($file));
    }

    public function getContainer(): string
    {
        return $this->container;
    }

    /**
     * @param T $file
     */
    public function getBlob(File $file): string
    {
        return $this->manager->getPathname($file);
    }
}
