<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;

interface AzureBlobStorageSASParametersFactory
{
    public function create(File $file): AzureBlobStorageSASParameters;
}
