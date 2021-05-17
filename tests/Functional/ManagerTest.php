<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\Functional\Entity\File;
use Arxy\FilesBundle\Tests\Functional\Entity\News;
use SplFileObject;

class ManagerTest extends AbstractFunctionalTest
{
    protected ?ManagerInterface $embeddableManager;

    protected static function getConfig(): string
    {
        return __DIR__.'/config.yml';
    }

    protected static function getBundles(): array
    {
        return [];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddableManager = self::$container->get('embeddable_manager');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->embeddableManager->clear();
        $this->embeddableManager = null;
    }

    public function testSimpleUpload(): File
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        self::assertSame(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            md5($this->flysystem->read('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1'))
        );

        return $file;
    }

    public function testSimpleUploadRelation(): News
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));

        $news = new News();
        $news->setFile($file);
        $this->entityManager->persist($news);
        $this->entityManager->flush();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        self::assertSame(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            md5($this->flysystem->read('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1'))
        );

        return $news;
    }

    public function testDeleteUploadRelation(): void
    {
        $news = $this->testSimpleUploadRelation();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $news = $this->entityManager->find(News::class, $news->getId());

        $this->entityManager->remove($news);
        $this->entityManager->flush();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
    }

    public function testSimpleUploadEmbeddable()
    {
        $file = $this->embeddableManager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));

        $news = new News();
        $news->setEmbeddableFile($file);
        $this->entityManager->persist($news);
        $this->entityManager->flush();

        $this->entityManager->clear();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        self::assertSame(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            md5($this->flysystem->read('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1'))
        );

        return $news;
    }

    public function testDeleteEmbeddable()
    {
        $news = $this->testSimpleUploadEmbeddable();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $news = $this->entityManager->find(News::class, $news->getId());

        $this->entityManager->remove($news);
        $this->entityManager->flush();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
    }

    /**
     * @depends testSimpleUpload
     */
    public function testSimpleDelete()
    {
        $file = $this->testSimpleUpload();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $this->entityManager->remove($file);

//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );

        $this->entityManager->flush();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
    }

//    /**
//     * @depends testSimpleUpload
//     */
//    public function testFileNotDeletedWithRollback()
//    {
//        $file = $this->testSimpleUpload();
//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->beginTransaction();
//
//        $this->entityManager->remove($file);
//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->flush();
//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->rollback();
//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//    }
//
//    /**
//     * @depends testSimpleUpload
//     */
//    public function testFileDeletedWithCommit()
//    {
//        $file = $this->testSimpleUpload();
//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->beginTransaction();
//
//        $this->entityManager->remove($file);
//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->flush();
//
//        self::assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->commit();
//
//        self::assertFalse(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//    }

    /**
     * @depends testSimpleUpload
     */
    public function testSameFileUpload()
    {
        $file = $this->testSimpleUpload();

        $file2 = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));

        $this->entityManager->persist($file2);
        $this->entityManager->flush();

        self::assertSame($file, $file2);
    }

    public function testSameFileUploadWithoutFlushInBetween()
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));
        $this->entityManager->persist($file);

        $file2 = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));
        $this->entityManager->persist($file2);

        self::assertSame($file, $file2);

        $this->entityManager->flush();

        $file3 = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));

        self::assertSame($file, $file3);
    }
}
