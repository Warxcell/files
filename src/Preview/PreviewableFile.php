<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 */
interface PreviewableFile extends File
{
    /**
     * @return T|null
     */
    public function getPreview(): ?File;

    /**
     * @param T|null $file
     */
    public function setPreview(?File $file): void;
}
