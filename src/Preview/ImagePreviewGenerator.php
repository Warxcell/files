<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Imagine\Filter\Transformation;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use SplFileInfo;
use SplTempFileObject;

class ImagePreviewGenerator implements PreviewGeneratorInterface
{
    private ManagerInterface $manager;
    private ImagineInterface $imagine;
    private ?string $format;
    private ?Transformation $transformation;

    public function __construct(
        ManagerInterface $manager,
        ImagineInterface $imagine,
        string $format = null,
        Transformation $transformation = null
    ) {
        $this->manager = $manager;
        $this->imagine = $imagine;
        $this->format = $format;
        $this->transformation = $transformation;
    }

    private function getFormat(File $file): string
    {
        if ($this->format !== null) {
            return $this->format;
        }

        return str_replace('image/', '', $file->getMimeType());
    }

    public function supports(File $file): bool
    {
        return stripos($file->getMimeType(), 'image/') !== false;
    }

    public function generate(File $file, DimensionInterface $dimension): SplFileInfo
    {
        $image = $this->imagine->read($this->manager->readStream($file));
        $image = $image->thumbnail(new Box($dimension->getWidth(), $dimension->getHeight()));

        if ($this->transformation !== null) {
            $this->transformation->apply($image);
        }

        $preview = new SplTempFileObject();
        $preview->fwrite($image->get($this->getFormat($file)));

        return $preview;
    }
}
