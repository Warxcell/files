<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use DateTime;
use MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper;
use MicrosoftAzure\Storage\Common\Internal\Resources;
use function sprintf;

class AzureBlobStorageSASPathResolver implements PathResolver
{
    private AzureBlobStoragePathResolver $pathResolver;
    private BlobSharedAccessSignatureHelper $signatureHelper;
    private AzureBlobStorageSASParametersFactory $parametersFactory;

    public function __construct(
        AzureBlobStoragePathResolver $pathResolver,
        BlobSharedAccessSignatureHelper $signatureHelper,
        AzureBlobStorageSASParametersFactory $factory
    ) {
        $this->pathResolver = $pathResolver;
        $this->signatureHelper = $signatureHelper;
        $this->parametersFactory = $factory;
    }

    public function getPath(File $file): string
    {
        return $this->pathResolver->getPath($file).'?'.$this->generateSas($file);
    }

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
