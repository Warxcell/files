<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\Functional\AbstractFunctionalTest;
use SplFileObject;

abstract class AbstractStrategyTest extends AbstractFunctionalTest
{
    private NamingStrategy $namingStrategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->namingStrategy = self::$container->get(NamingStrategy::class);
    }

    final public function doesFileExists(File $file): bool
    {
        $filepath = ($this->namingStrategy->getDirectoryName($file) ?? "") . $this->namingStrategy->getFileName($file);

        return $this->flysystem->fileExists($filepath);
    }

    final public function testFileAfterCreation(): File
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__ . '/../../files/image1.jpg'));

        $this->entityManager->persist($file);
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertTrue($this->doesFileExists($file));

        return $file;
    }

    // not working because Doctrine unsets the ID after deletion
    //    final public function testFileAfterDeletion()
    //    {
    //        $file = $this->testFileAfterCreation();
    //
    //        $file = $this->entityManager->find($this->manager->getClass(), $file->getId());
    //
    //        $filepath = ($this->namingStrategy->getDirectoryName($file) ?? "").$this->namingStrategy->getFileName($file);
    //
    //        $this->entityManager->remove($file);
    ////        $this->assertTrue($this->flysystem->fileExists($filepath));
    //
    //        $this->entityManager->flush();
    //        self::assertFalse($this->flysystem->fileExists($filepath));
    //    }
}
