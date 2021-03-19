<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class File extends \Symfony\Component\Validator\Constraints\File
{
}
