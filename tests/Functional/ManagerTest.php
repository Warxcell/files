<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\Functional\Entity\File;
use Arxy\FilesBundle\Tests\Functional\Entity\News;
use SplFileObject;
use SplTempFileObject;

use function md5;

class ManagerTest extends AbstractFunctionalTest
{
    protected ManagerInterface $embeddableManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddableManager = self::$container->get('embeddable_manager');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->embeddableManager->clear();
        unset($this->embeddableManager);
    }

    protected static function getConfig(): string
    {
        return __DIR__ . '/config.yml';
    }

    protected static function getBundles(): array
    {
        return [];
    }

    public function testSimpleUpload(): File
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));

        $this->entityManager->persist($file);

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
        $this->entityManager->flush();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        self::assertSame(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            md5($this->flysystem->read('9aa1c5fc7c9388166d7ce7fd46648dd1'))
        );

        return $file;
    }

    public function testTempFileUpload(): void
    {
        $content = 'this is temporary file upload test';
        $tmpFile = new SplTempFileObject(0);
        $tmpFile->fwrite($content);
        $file = $this->manager->upload($tmpFile);

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        self::assertTrue(
            $this->flysystem->fileExists('fa0ac560d8862aadfbe18ed19dc8663d')
        );

        self::assertSame('this is temporary file upload test', $this->manager->read($file));

        self::assertSame(
            'fa0ac560d8862aadfbe18ed19dc8663d',
            md5($this->flysystem->read('fa0ac560d8862aadfbe18ed19dc8663d'))
        );
    }

    public function testSimpleUploadRelation(): News
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));

        $news = new News();
        $news->setFile($file);
        $this->entityManager->persist($news);
        $this->entityManager->flush();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        self::assertSame(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            md5($this->flysystem->read('9aa1c5fc7c9388166d7ce7fd46648dd1'))
        );

        return $news;
    }

    public function testDeleteUploadRelation(): void
    {
        $news = $this->testSimpleUploadRelation();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $news = $this->entityManager->find(News::class, $news->getId());

        self::assertNotNull($news);
        $this->entityManager->remove($news);
        $this->entityManager->flush();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
    }

    public function testSimpleUploadEmbeddable(): News
    {
        $file = $this->embeddableManager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));
        $file2 = $this->embeddableManager->upload(new SplFileObject(__DIR__ . '/../files/image2.jpg'));

        $news = new News();
        $news->setEmbeddableFile($file);
        $news->setEmbeddableFile1($file2);
        $this->entityManager->persist($news);
        $this->entityManager->flush();

        $this->entityManager->clear();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
        self::assertSame(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            md5($this->flysystem->read('9aa1c5fc7c9388166d7ce7fd46648dd1'))
        );

        self::assertTrue(
            $this->flysystem->fileExists('59aeac36ae75786be1b573baad0e77c0')
        );
        self::assertSame(
            '59aeac36ae75786be1b573baad0e77c0',
            md5($this->flysystem->read('59aeac36ae75786be1b573baad0e77c0'))
        );

        return $news;
    }

    public function testDeleteEmbeddable(): void
    {
        $news = $this->testSimpleUploadEmbeddable();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $news = $this->entityManager->find(News::class, $news->getId());

        self::assertNotNull($news);
        $this->entityManager->remove($news);
        $this->entityManager->flush();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
    }

    /**
     * @depends testSimpleUpload
     */
    public function testSimpleDelete(): void
    {
        $file = $this->testSimpleUpload();

        $pathname = '9aa1c5fc7c9388166d7ce7fd46648dd1';

        self::assertTrue($this->flysystem->fileExists($pathname));

        $this->entityManager->remove($file);

        self::assertTrue($this->flysystem->fileExists($pathname));

        $this->entityManager->flush();

        self::assertFalse($this->flysystem->fileExists($pathname));
    }

    public function testFileUploadedWithClear(): void
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));

        $this->entityManager->beginTransaction();

        $this->entityManager->persist($file);

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
        $this->entityManager->flush();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $this->entityManager->clear();

        $this->entityManager->commit();

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
    }

    public function testFileNotUploadedWithRollBack(): void
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));

        $this->entityManager->beginTransaction();

        $this->entityManager->persist($file);

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
        $this->entityManager->flush();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $this->entityManager->rollback();

        self::assertFalse(
            $this->flysystem->fileExists('9aa1c5fc7c9388166d7ce7fd46648dd1')
        );
    }

    /**
     * @depends testSimpleUpload
     */
    public function testFileNotDeletedWithRollback(): void
    {
        $file = $this->testSimpleUpload();

        $filepath = '9aa1c5fc7c9388166d7ce7fd46648dd1';
        self::assertTrue($this->flysystem->fileExists($filepath));

        $this->entityManager->beginTransaction();

        $this->entityManager->remove($file);

        self::assertTrue($this->flysystem->fileExists($filepath));

        $this->entityManager->flush();

        self::assertTrue($this->flysystem->fileExists($filepath));

        $this->entityManager->rollback();

        self::assertTrue($this->flysystem->fileExists($filepath));
    }

    /**
     * @depends testSimpleUpload
     */
    public function testFileDeletedWithCommit(): void
    {
        $file = $this->testSimpleUpload();

        $filepath = '9aa1c5fc7c9388166d7ce7fd46648dd1';

        self::assertTrue($this->flysystem->fileExists($filepath));

        $this->entityManager->beginTransaction();

        $this->entityManager->remove($file);

        self::assertTrue($this->flysystem->fileExists($filepath));

        $this->entityManager->flush();

        self::assertTrue($this->flysystem->fileExists($filepath));

        $this->entityManager->commit();

        self::assertFalse($this->flysystem->fileExists($filepath));
    }

    /**
     * @depends testSimpleUpload
     */
    public function testSameFileUpload(): void
    {
        $file = $this->testSimpleUpload();

        $file2 = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));

        $this->entityManager->persist($file2);
        $this->entityManager->flush();

        self::assertSame($file, $file2);
    }

    public function testSameFileUploadWithoutFlushInBetween(): void
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));
        $this->entityManager->persist($file);

        $file2 = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));
        $this->entityManager->persist($file2);

        self::assertSame($file, $file2);

        $this->entityManager->flush();

        $file3 = $this->manager->upload(new SplFileObject(__DIR__ . '/../files/image1.jpg'));

        self::assertSame($file, $file3);
    }
}
