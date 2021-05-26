<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Model\MutableFile;
use SplFileInfo;
use function pathinfo;
use function sprintf;

class PreviewGenerator
{
    private ManagerInterface $manager;

    /** @var PreviewGeneratorInterface[] */
    private iterable $generators;

    private DimensionInterface $dimension;

    /**
     * @param PreviewGeneratorInterface[] $generators
     */
    public function __construct(ManagerInterface $manager, iterable $generators, DimensionInterface $dimension)
    {
        $this->manager = $manager;
        $this->generators = $generators;
        $this->dimension = $dimension;
    }

    private function generatePreview(File $file): SplFileInfo
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($file)) {
                return $generator->generate($file, $this->dimension);
            }
        }

        throw NoPreviewGeneratorFound::instance($file);
    }

    /**
     * @throws NoPreviewGeneratorFound
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
}
