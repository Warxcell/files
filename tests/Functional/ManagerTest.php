<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\Functional\Entity\File;
use Arxy\FilesBundle\Tests\Functional\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use SplFileObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ManagerTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ManagerInterface $manager;
    private ?ManagerInterface $embeddableManager;
    private ?FilesystemOperator $flysystem;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->buildDb($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->manager = self::$container->get(ManagerInterface::class);
        $this->embeddableManager = self::$container->get('embeddable_manager');
        $this->flysystem = self::$container->get('in_memory');
    }

    private function buildDb($kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $application->run(
            new ArrayInput(
                [
                    'doctrine:schema:create',
                ]
            ),
            new ConsoleOutput()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;

        $this->manager->clear();
        $this->manager = null;

        $this->embeddableManager->clear();
        $this->embeddableManager = null;

        $this->flysystem = null;
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

        self::assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

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