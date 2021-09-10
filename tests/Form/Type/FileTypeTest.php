<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Form\Type;

use Arxy\FilesBundle\Form\Type\FileType;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\File;
use stdClass;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FileTypeTest extends TypeTestCase
{
    private ManagerInterface $manager;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ManagerInterface::class);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new FileType($this->manager);

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    protected function getTypeExtensions(): array
    {
        return [
            new FormTypeHttpFoundationExtension(),
        ];
    }

    public function testSingleUpload(): void
    {
        $uploadedFile = new UploadedFile(__DIR__ . '/../../files/image1.jpg', 'image1.jpg');

        $file = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager->expects($this->once())->method('upload')->with($uploadedFile)->willReturn($file);
        $this->manager->expects($this->once())->method('getClass')->willReturn(File::class);

        $form = $this->factory->create(FileType::class, null);

        $form->submit(['file' => $uploadedFile]);

        $actual = $form->getData();

        self::assertTrue($form->isSynchronized());
        self::assertInstanceOf(File::class, $actual);
        self::assertSame($file, $actual);
    }

    public function testMultipleUpload(): void
    {
        $uploadedFile1 = new UploadedFile(__DIR__ . '/../../files/image1.jpg', 'image1.jpg');
        $uploadedFile2 = new UploadedFile(__DIR__ . '/../../files/image2.jpg', 'image2.jpg');

        $file1 = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2 = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');

        $this->manager->expects($this->exactly(2))
            ->method('upload')
            ->withConsecutive(
                [$this->identicalTo($uploadedFile1)],
                [$this->identicalTo($uploadedFile2)]
            )
            ->will($this->onConsecutiveCalls($file1, $file2));

        $this->manager->method('getClass')->willReturn(File::class);

        $form = $this->factory->create(
            FileType::class,
            null,
            [
                'multiple' => true,
            ]
        );

        $form->submit(['file' => [$uploadedFile1, $uploadedFile2]]);

        $actual = $form->getData();

        self::assertTrue($form->isSynchronized());
        self::assertCount(2, $actual);
        self::assertSame($file1, $actual[0]);
        self::assertSame($file2, $actual[1]);
    }

    public function testInvalidManagerPassed()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage(
            'The option "manager" with value stdClass is expected to be of type "Arxy\FilesBundle\ManagerInterface", but is of type "stdClass".'
        );

        $this->factory->create(
            FileType::class,
            null,
            [
                'manager' => new stdClass(),
            ]
        );
    }

    public function testLabelOfFileIsFalse()
    {
        $form = $this->factory->create(
            FileType::class,
            null,
            [
                'data_class' => File::class,
            ]
        );

        self::assertFalse($form->get('file')->getConfig()->getOption('label'));
    }
}
