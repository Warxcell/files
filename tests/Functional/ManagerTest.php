<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional;

use Arxy\FilesBundle\ManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use SplFileObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ManagerTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ManagerInterface $manager;
    private ?FilesystemOperator $flysystem;

    public function testSimpleUpload()
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        $this->assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $this->assertSame(
            '9aa1c5fc7c9388166d7ce7fd46648dd1',
            md5($this->flysystem->read('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1'))
        );

        return $file;
    }

    /**
     * @depends testSimpleUpload
     */
    public function testSimpleDelete()
    {
        $file = $this->testSimpleUpload();

        $this->assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $this->entityManager->remove($file);

        $this->assertTrue(
            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
        );

        $this->entityManager->flush();

        $this->assertFalse(
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
//        $this->assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->beginTransaction();
//
//        $this->entityManager->remove($file);
//
//        $this->assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->flush();
//
//        $this->assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->rollback();
//
//        $this->assertTrue(
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
//        $this->assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->beginTransaction();
//
//        $this->entityManager->remove($file);
//
//        $this->assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->flush();
//
//        $this->assertTrue(
//            $this->flysystem->fileExists('9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1')
//        );
//
//        $this->entityManager->commit();
//
//        $this->assertFalse(
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

        $this->assertSame($file, $file2);
    }

    public function testSameFileUploadWithoutFlushInBetween()
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));
        $this->entityManager->persist($file);

        $file2 = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));
        $this->entityManager->persist($file2);

        $this->assertSame($file, $file2);

        $this->entityManager->flush();

        $file3 = $this->manager->upload(new SplFileObject(__DIR__.'/../files/image1.jpg'));

        $this->assertSame($file, $file3);
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->buildDb($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->manager = self::$container->get(ManagerInterface::class);
        $this->flysystem = self::$container->get(FilesystemOperator::class);
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
            new NullOutput()
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;

        $this->manager->clear();
        $this->manager = null;

        $this->flysystem = null;
    }
}