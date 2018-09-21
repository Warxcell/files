<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Validator\Constraint;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;

class FileValidator extends \Symfony\Component\Validator\Constraints\FileValidator
{
    private static $suffices = array(
        1 => 'bytes',
        self::KB_BYTES => 'kB',
        self::MB_BYTES => 'MB',
        self::KIB_BYTES => 'KiB',
        self::MIB_BYTES => 'MiB',
    );

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof File) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\File');
        }
        if (!$value instanceof \Arxy\FilesBundle\Model\File) {
            throw new UnexpectedTypeException($value, \Arxy\FilesBundle\Model\File::class);
        }

        if ($constraint->maxSize) {
            $limitInBytes = $constraint->maxSize;

            $sizeInBytes = $value->getFileSize();

            if ($sizeInBytes > $limitInBytes) {
                list($sizeAsString, $limitAsString, $suffix) = $this->factorizeSizes(
                    $sizeInBytes,
                    $limitInBytes,
                    $constraint->binaryFormat
                );
                $this->context->buildViolation($constraint->maxSizeMessage)
                    ->setParameter('{{ size }}', $sizeAsString)
                    ->setParameter('{{ limit }}', $limitAsString)
                    ->setParameter('{{ suffix }}', $suffix)
                    ->setCode(File::TOO_LARGE_ERROR)
                    ->addViolation();

                return;
            }
        }

        if ($constraint->mimeTypes) {
            $mimeTypes = (array)$constraint->mimeTypes;
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
                ->setCode(File::INVALID_MIME_TYPE_ERROR)
                ->addViolation();
        }
    }

    private static function moreDecimalsThan($double, $numberOfDecimals)
    {
        return \strlen((string)$double) > \strlen((string)round($double, $numberOfDecimals));
    }

    private function factorizeSizes($size, $limit, $binaryFormat)
    {
        if ($binaryFormat) {
            $coef = self::MIB_BYTES;
            $coefFactor = self::KIB_BYTES;
        } else {
            $coef = self::MB_BYTES;
            $coefFactor = self::KB_BYTES;
        }

        $limitAsString = (string)($limit / $coef);

        // Restrict the limit to 2 decimals (without rounding! we
        // need the precise value)
        while (self::moreDecimalsThan($limitAsString, 2)) {
            $coef /= $coefFactor;
            $limitAsString = (string)($limit / $coef);
        }

        // Convert size to the same measure, but round to 2 decimals
        $sizeAsString = (string)round($size / $coef, 2);

        // If the size and limit produce the same string output
        // (due to rounding), reduce the coefficient
        while ($sizeAsString === $limitAsString) {
            $coef /= $coefFactor;
            $limitAsString = (string)($limit / $coef);
            $sizeAsString = (string)round($size / $coef, 2);
        }

        return array($sizeAsString, $limitAsString, self::$suffices[$coef]);
    }
}