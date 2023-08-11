<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Validator\Constraint;

use Attribute;
use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

use function ByteUnits\parse;
use function is_array;
use function is_string;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class File extends Constraint
{
    public ?int $maxSize = null;

    /** @var array<int, string> */
    public array $mimeTypes = [];

    /**
     * @param array<string>|string $mimeTypes
     * @param array<string>|null $groups
     */
    public function __construct(
        int|string|null $maxSize,
        public string $maxSizeMessage = 'The file is too large ({{ size }}). Allowed maximum size is {{ limit }}.',
        array|string $mimeTypes = [],
        public string $mimeTypesMessage = 'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.',
        array $groups = null,
    ) {
        if (is_string($maxSize)) {
            $maxSize = $this->normalizeBinaryFormat($maxSize);
        }
        $this->maxSize = $maxSize;

        if (!is_array($mimeTypes)) {
            $mimeTypes = [$mimeTypes];
        }
        $this->mimeTypes = $mimeTypes;
        parent::__construct(groups: $groups);
    }

    private function normalizeBinaryFormat(string $maxSize): int
    {
        $original = $maxSize;
        try {
            if (stripos($maxSize, 'B') === false) {
                $maxSize .= 'B';
            }

            return (int)parse($maxSize)->numberOfBytes();
        } catch (Exception $e) {
            throw new ConstraintDefinitionException(sprintf('"%s" is not a valid maximum size.', $original), 0, $e);
        }
    }
}
