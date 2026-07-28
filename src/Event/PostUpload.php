<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Event;

use Arxy\FilesBundle\Model\File;

/**
 * @template T of File
 * @template C
 * @extends AbstractFileEvent<T, C>
 */
final class PostUpload extends AbstractFileEvent
{
}
