<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

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
        if (isset($options['maxSize'])) {
            $options['maxSize'] = $this->normalizeBinaryFormat($options['maxSize']);
        }

        if (isset($options['mimeTypes'])) {
            $options['mimeTypes'] = (array)$options['mimeTypes'];
        }
        parent::__construct($options, $groups, $payload);
    }

    private function normalizeBinaryFormat($maxSize): int
    {
        $factors = [
            'k' => 1000,
            'ki' => 1 << 10,
            'm' => 1000 * 1000,
            'mi' => 1 << 20,
            'g' => 1000 * 1000 * 1000,
            'gi' => 1 << 30,
        ];
        if (ctype_digit((string)$maxSize)) {
            return (int)$maxSize;
        } elseif (preg_match('/^(\d++)('.implode('|', array_keys($factors)).')$/i', $maxSize, $matches)) {
            return (int)($matches[1] * $factors[$unit = strtolower($matches[2])]);
        } else {
            throw new ConstraintDefinitionException(sprintf('"%s" is not a valid maximum size.', $maxSize));
        }
    }
}
