<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Validator\Constraint;

use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Validator\Constraint\FileValidator;
use stdClass;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FileValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): FileValidator
    {
        return new FileValidator();
    }

    public function testNotValidValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Arxy\FilesBundle\Model\File", "stdClass" given');

        $this->validator->validate(
            new stdClass(),
            new \Arxy\FilesBundle\Validator\Constraint\File(
                maxSize: 10000,
            )
        );
    }

    public function testNotValidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Arxy\FilesBundle\Validator\Constraint\File", "Symfony\Component\Validator\Constraint@anonymous" given'
        );

        $this->validator->validate(
            new File('filename', 125, '12345', 'image/jpeg'),
            new class extends Constraint {
            }
        );
    }

    public function testNull(): void
    {
        $this->validator->validate(
            null,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                maxSize: 10000,
            )
        );
        self::assertNoViolation();
    }

    public function testInvalidSize(): void
    {
        $file = new File('filename', 1000, '12345', 'image/jpeg');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                maxSize: 100,
            )
        );

        $this->buildViolation(
            'The file is too large ({{ size }}). Allowed maximum size is {{ limit }}.'
        )
            ->setParameter('{{ size }}', '1.00 kB')
            ->setParameter('{{ limit }}', '100 B')
            ->assertRaised();
    }

    public function testValidExactSize(): void
    {
        $file = new File('filename', 1000, '12345', 'image/jpeg');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                maxSize: 1000,
            )
        );
        self::assertNoViolation();
    }

    public function testValidSize(): void
    {
        $file = new File('filename', 1000, '12345', 'image/jpeg');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                maxSize: 10000,
            )
        );
        self::assertNoViolation();
    }

    public function testInvalidMimeType(): void
    {
        $file = new File('filename', 1000, '12345', 'text/html');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                mimeTypes: ['image/*', 'application/pdf'],
            )
        );

        $this->buildViolation(
            'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.'
        )
            ->setParameter('{{ type }}', '"text/html"')
            ->setParameter('{{ types }}', '"image/*", "application/pdf"')
            ->assertRaised();
    }

    public function testValidMimeType(): void
    {
        $file = new File('filename', 1000, '12345', 'image/jpeg');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                mimeTypes: ['image/*', 'application/pdf'],
            )
        );

        $file = new File('filename', 1000, '12345', 'application/pdf');
        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                mimeTypes: ['image/*', 'application/pdf'],
            )
        );

        self::assertNoViolation();
    }

    public function testInvalidSizeAndMimeType(): void
    {
        $file = new File('filename', 1025, '12345', 'text/html');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                maxSize: 1000,
                mimeTypes: ['image/*', 'application/pdf'],
            )
        );

        $this->buildViolation(
            'The file is too large ({{ size }}). Allowed maximum size is {{ limit }}.'
        )
            ->setParameter('{{ size }}', '1.02 kB')
            ->setParameter('{{ limit }}', '1.00 kB')
            ->buildNextViolation(
                'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.'
            )
            ->setParameter('{{ type }}', '"text/html"')
            ->setParameter('{{ types }}', '"image/*", "application/pdf"')
            ->assertRaised();
    }
}
