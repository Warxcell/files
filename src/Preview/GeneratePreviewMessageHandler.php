<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Preview;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GeneratePreviewMessageHandler implements MessageHandlerInterface
{
    private PreviewGenerator $generator;

    public function __construct(PreviewGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function __invoke(GeneratePreviewMessage $message)
    {
        $file = $message->getFile();
        try {
            $file->setPreview($this->generator->generate($file));
        } catch (NoPreviewGeneratorFound $exception) {
        }
    }
}
