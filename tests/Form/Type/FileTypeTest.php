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

    public function testSimpleUpload()
    {
        $uploadedFile = new UploadedFile(__DIR__.'/../../files/image1.jpg', 'image1.jpg');

        $file = new File();

        $this->manager->expects($this->once())->method('upload')->with($uploadedFile)->willReturn($file);
        $this->manager->expects($this->once())->method('getClass')->willReturn(File::class);

        $form = $this->factory->create(
            FileType::class,
            null
        );

        $form->submit(['file' => $uploadedFile]);

        $actual = $form->getData();

        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(File::class, $actual);
        $this->assertSame($file, $actual);
    }
}