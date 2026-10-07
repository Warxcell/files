<?php

declare(strict_types=1);

namespace Arxy\FilesBundle;

use Arxy\FilesBundle\Model\File;
use SplFileInfo;

/**
 * @template T of File
 * @template C
 */
interface UploaderInterface
{
    /**
     * Converts SplFileInfo instance to file object.
     * @param SplFileInfo $splFileInfo
     * @param C $context
     * @return T
     * @throws UnableToUpload
     */
    public function upload(SplFileInfo $splFileInfo, mixed $context = null): File;
}
