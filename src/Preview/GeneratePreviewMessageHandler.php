<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Arxy\FilesBundle\UnableToUpload;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GeneratePreviewMessageHandler
{
    private PreviewGenerator $generator;

    public function __construct(PreviewGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @throws UnableToUpload
     */
    public function __invoke(GeneratePreviewMessage $message): void
    {
        $file = $message->getFile();
        try {
            $file->setPreview($this->generator->generate($file));
        } catch (NoPreviewGeneratorFound) {
        }
    }
}
