<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use Arxy\FilesBundle\UploaderInterface;
use SplFileInfo;

use function pathinfo;
use function sprintf;

class PreviewGenerator
{
    /**
     * @param UploaderInterface<File, mixed> $manager
     * @param PreviewGeneratorInterface[] $generators
     */
    public function __construct(
        private readonly UploaderInterface $manager,
        private readonly iterable $generators,
        private readonly DimensionInterface $dimension
    ) {
    }

    /**
     * @throws NoPreviewGeneratorFound
     * @throws \Arxy\FilesBundle\UnableToUpload
     */
    public function generate(File $file): File
    {
        $preview = $this->manager->upload($this->generatePreview($file));

        if ($preview instanceof MutableFile) {
            $filename = pathinfo($file->getOriginalFilename(), PATHINFO_FILENAME);
            $extension = pathinfo($file->getOriginalFilename(), PATHINFO_EXTENSION);
            $preview->setOriginalFilename(sprintf('%s_preview.%s', $filename, $extension));
        }

        return $preview;
    }

    /**
     * @throws NoPreviewGeneratorFound
     */
    private function generatePreview(File $file): SplFileInfo
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($file)) {
                return $generator->generate($file, $this->dimension);
            }
        }

        throw NoPreviewGeneratorFound::instance($file);
    }
}
