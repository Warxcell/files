<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;
use SplFileInfo;

interface PreviewGeneratorInterface
{
    public function supports(File $file): bool;

    public function generate(File $file, DimensionInterface $dimension): SplFileInfo;
}
