<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use DateTime;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Common\Internal\Resources;

use function sprintf;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class AzureBlobStorageSASPathResolver implements PathResolver
{
    /**
     * @param AzureBlobStoragePathResolver<T> $pathResolver
     */
    public function __construct(
        private readonly AzureBlobStoragePathResolver $pathResolver,
        private readonly BlobSharedAccessSignatureHelper $signatureHelper,
        private readonly AzureBlobStorageSASParametersFactory $parametersFactory
    ) {
    }

    #[\Override]
    public function getPath(File $file): string
    {
        return $this->pathResolver->getPath($file) . '?' . $this->generateSas($file);
    }

    /**
     * @param T $file
     */
    private function generateSas(File $file): string
    {
        $parameters = $this->parametersFactory->create($file);
        $expiry = $parameters->getExpiry();

        $expiry = DateTime::createFromImmutable($expiry);

        $start = $parameters->getStart();
        if ($start !== null) {
            $start = DateTime::createFromImmutable($start);
        }

        return $this->signatureHelper->generateBlobServiceSharedAccessSignatureToken(
            Resources::RESOURCE_TYPE_BLOB,
            sprintf('%s/%s', $this->pathResolver->getContainer(), $this->pathResolver->getBlob($file)),
            'r',
            $expiry,
            $start ?? "",
            $parameters->getIp() ?? "",
            'https',
            $parameters->getIdentifier() ?? "",
            $parameters->getCacheControl() ?? "",
            $parameters->getContentDisposition() ?? "",
            $parameters->getContentEncoding() ?? "",
            $parameters->getContentLanguage() ?? "",
            $parameters->getContentType() ?? "",
        );
    }
}
