<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Validator\Constraint;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function ByteUnits\bytes;

class FileValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof File) {
            throw new UnexpectedTypeException($constraint, File::class);
        }
        if ($value === null) {
            return;
        }
        if (!$value instanceof \Arxy\FilesBundle\Model\File) {
            throw new UnexpectedTypeException($value, \Arxy\FilesBundle\Model\File::class);
        }

        if ($constraint->maxSize !== null) {
            $limitInBytes = $constraint->maxSize;
            $sizeInBytes = $value->getFileSize();

            if ($sizeInBytes > $limitInBytes) {
                $this->context->buildViolation($constraint->maxSizeMessage)
                    ->setParameter('{{ size }}', $this->humanizeBytes($sizeInBytes))
                    ->setParameter('{{ limit }}', $this->humanizeBytes($limitInBytes))
                    ->addViolation();
            }
        }

        if (count($constraint->mimeTypes) > 0) {
            $mimeTypes = $constraint->mimeTypes;
            $mime = $value->getMimeType();

            foreach ($mimeTypes as $mimeType) {
                if ($mimeType === $mime) {
                    return;
                }

                if ($discrete = strstr($mimeType, '/*', true)) {
                    if (strstr($mime, '/', true) === $discrete) {
                        return;
                    }
                }
            }

            $this->context->buildViolation($constraint->mimeTypesMessage)
                ->setParameter('{{ type }}', $this->formatValue($mime))
                ->setParameter('{{ types }}', $this->formatValues($mimeTypes))
                ->addViolation();
        }
    }

    private function humanizeBytes(int $bytes): string
    {
        return bytes($bytes)->format(2, ' ');
    }
}
