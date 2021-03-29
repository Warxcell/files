<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Form\Type;

use Arxy\FilesBundle\Form\Type\FileType;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\File;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileTypeTest extends TypeTestCase
{
    private ManagerInterface $manager;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ManagerInterface::class);

        parent::setUp();
    }

    protected function getExtensions()
    {
        $type = new FileType($this->manager);

        return [
            new PreloadedExtension([$type], []),

        ];
    }

    protected function getTypeExtensions()
    {
        return [
            new FormTypeHttpFoundationExtension(),
        ];
    }

    public function testSingleUpload()
    {
        $uploadedFile = new UploadedFile(__DIR__.'/../../files/image1.jpg', 'image1.jpg');

        $file = new File();

        $this->manager->expects($this->once())->method('upload')->with($uploadedFile)->willReturn($file);
        $this->manager->expects($this->once())->method('getClass')->willReturn(File::class);

        $form = $this->factory->create(FileType::class, null);

        $form->submit(['file' => $uploadedFile]);

        $actual = $form->getData();

        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(File::class, $actual);
        $this->assertSame($file, $actual);
    }

    public function testMultipleUpload()
    {
        $uploadedFile1 = new UploadedFile(__DIR__.'/../../files/image1.jpg', 'image1.jpg');
        $uploadedFile2 = new UploadedFile(__DIR__.'/../../files/image2.jpg', 'image2.jpg');

        $file1 = new File();
        $file2 = new File();

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

        $this->assertTrue($form->isSynchronized());
        $this->assertCount(2, $actual);
        $this->assertSame($file1, $actual[0]);
        $this->assertSame($file2, $actual[1]);
    }
}
