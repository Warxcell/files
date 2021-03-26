<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Validator;

use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Validator\Constraint\FileValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FileValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new FileValidator();
    }

    public function testInvalidSize()
    {
        $file = new File();
        $file->setFileSize(1000);

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                [
                    'maxSize' => 100,
                ]
            )
        );

        $this->buildViolation(
            'The file is too large ({{ size }}). Allowed maximum size is {{ limit }}.'
        )
            ->setParameter('{{ size }}', '1000 bytes')
            ->setParameter('{{ limit }}', '100 bytes')
            ->assertRaised();
    }

    public function testValidSize()
    {
        $file = new File();
        $file->setFileSize(1000);

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                [
                    'maxSize' => 10000,
                ]
            )
        );
        $this->assertNoViolation();
    }

    public function testInvalidMimeType()
    {
        $file = new File();
        $file->setFileSize(1000);
        $file->setMimeType('text/html');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                [
                    'mimeTypes' => ['image/*', 'application/pdf'],
                ]
            )
        );

        $this->buildViolation(
            'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.'
        )
            ->setParameter('{{ type }}', '"text/html"')
            ->setParameter('{{ types }}', '"image/*", "application/pdf"')
            ->assertRaised();
    }

    public function testValidMimeType()
    {
        $file = new File();
        $file->setFileSize(1000);
        $file->setMimeType('image/jpeg');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                [
                    'mimeTypes' => ['image/*', 'application/pdf'],
                ]
            )
        );

        $file = new File();
        $file->setFileSize(1000);
        $file->setMimeType('application/pdf');
        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                [
                    'mimeTypes' => ['image/*', 'application/pdf'],
                ]
            )
        );

        $this->assertNoViolation();
    }

    public function testInvalidSizeAndMimeType()
    {
        $file = new File();
        $file->setFileSize(1025);
        $file->setMimeType('text/html');

        $this->validator->validate(
            $file,
            new \Arxy\FilesBundle\Validator\Constraint\File(
                [
                    'maxSize' => 1000,
                    'mimeTypes' => ['image/*', 'application/pdf'],
                ]
            )
        );

        $this->buildViolation(
            'The file is too large ({{ size }}). Allowed maximum size is {{ limit }}.'
        )
            ->setParameter('{{ size }}', '1 KB')
            ->setParameter('{{ limit }}', '1000 bytes')
            ->buildNextViolation(
                'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.'
            )
            ->setParameter('{{ type }}', '"text/html"')
            ->setParameter('{{ types }}', '"image/*", "application/pdf"')
            ->assertRaised();
    }
}