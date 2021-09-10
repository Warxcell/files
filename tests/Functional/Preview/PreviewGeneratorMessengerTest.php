<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Preview;

use Arxy\FilesBundle\Tests\Functional\AbstractFunctionalTest;
use Arxy\FilesBundle\Tests\Functional\Entity\FileWithPreview;
use SplFileInfo;

class PreviewGeneratorMessengerTest extends AbstractFunctionalTest
{
    protected static function getConfig(): string
    {
        return __DIR__ . '/config_messenger.yml';
    }

    protected static function getBundles(): array
    {
        return [];
    }

    public function testImagePreview(): void
    {
        $file = $this->manager->upload(new SplFileInfo(__DIR__ . '/../../files/image1.jpg'));
        assert($file instanceof FileWithPreview);

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        self::assertNotNull($file->getPreview());
        //        $previewManager = self::$container->get('preview');

        //        $expectedFilename = __DIR__.'/../../files/image1_preview.jpg';
        //
        //        $expectedMd5 = md5_file($expectedFilename);
        //        self::assertSame($expectedMd5, md5($previewManager->read($file->getPreview())));
        //        self::assertSame($expectedMd5, $file->getPreview()->getHash());
        //
        //
        //        $expectedFilesize = filesize($expectedFilename);
        //        self::assertSame($expectedFilesize, strlen($previewManager->read($file->getPreview())));
        //        self::assertSame($expectedFilesize, $file->getPreview()->getSize());

        //        self::assertSame('image1_preview.jpg', $file->getPreview()->getOriginalFilename());
    }

    public function testPreviewWrite(): void
    {
        $file = $this->manager->upload(new SplFileInfo(__DIR__ . '/../../files/image1.jpg'));
        assert($file instanceof FileWithPreview);

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        $preview1 = $file->getPreview();
        self::assertNotNull($preview1);

        $this->manager->write($file, new SplFileInfo(__DIR__ . '/../../files/image2.jpg'));

        self::assertNotSame($preview1, $file->getPreview());
    }
}
