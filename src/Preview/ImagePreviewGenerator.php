<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\ReaderInterface;
use Imagine\Filter\Transformation;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use SplFileInfo;
use SplTempFileObject;

use function str_replace;
use function stripos;

class ImagePreviewGenerator implements PreviewGeneratorInterface
{
    /**
     * @param ReaderInterface<File> $manager
     */
    public function __construct(
        private readonly ReaderInterface $manager,
        private readonly ImagineInterface $imagine,
        private readonly ?string $format = null,
        private readonly ?Transformation $transformation = null
    ) {
    }

    #[\Override]
    public function supports(File $file): bool
    {
        return stripos($file->getMimeType(), 'image/') !== false;
    }

    /**
     * @throws \Arxy\FilesBundle\FileException
     * @throws \Imagine\Exception\InvalidArgumentException
     * @throws \Imagine\Exception\RuntimeException
     * @throws \RuntimeException
     */
    #[\Override]
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

    private function getFormat(File $file): string
    {
        return $this->format ?? str_replace('image/', '', $file->getMimeType());
    }
}
