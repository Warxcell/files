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

    final public function testFileAfterCreation(): File
    {
        $file = $this->manager->upload(new SplFileObject(__DIR__.'/../../files/image1.jpg'));

        $this->entityManager->persist($file);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $filepath = ($this->namingStrategy->getDirectoryName($file) ?? "").$this->namingStrategy->getFileName($file);
        $this->assertTrue($this->flysystem->fileExists($filepath));

        return $file;
    }

    final public function testFileAfterDeletion()
    {
        $file = $this->testFileAfterCreation();

        $file = $this->entityManager->find($this->manager->getClass(), $file->getId());

        $filepath = ($this->namingStrategy->getDirectoryName($file) ?? "").$this->namingStrategy->getFileName($file);

        $this->entityManager->remove($file);
//        $this->assertTrue($this->flysystem->fileExists($filepath));

        $this->entityManager->flush();
        $this->assertFalse($this->flysystem->fileExists($filepath));
    }
}
