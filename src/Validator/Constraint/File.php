<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Validator\Constraint;

use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use function ByteUnits\parse;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class File extends Constraint
{
    public ?int $maxSize = null;
    public ?string $maxSizeMessage = 'The file is too large ({{ size }}). Allowed maximum size is {{ limit }}.';
    public array $mimeTypes = [];
    public ?string $mimeTypesMessage = 'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.';

    public function __construct($options = null, array $groups = null, $payload = null)
    {
        if (isset($options['maxSize']) && is_string($options['maxSize'])) {
            $options['maxSize'] = $this->normalizeBinaryFormat($options['maxSize']);
        }

        if (isset($options['mimeTypes']) && !is_array($options['mimeTypes'])) {
            $options['mimeTypes'] = [$options['mimeTypes']];
        }
        parent::__construct($options, $groups, $payload);
    }

    private function normalizeBinaryFormat(string $maxSize): int
    {
        $original = $maxSize;
        try {
            if (stripos('B', $maxSize) === false) {
                $maxSize .= 'B';
            }

            return (int)parse($maxSize)->numberOfBytes();
        } catch (Exception $e) {
            throw new ConstraintDefinitionException(sprintf('"%s" is not a valid maximum size.', $original), 0, $e);
        }
    }
}
