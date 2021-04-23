<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Command;

use Arxy\FilesBundle\Command\RefreshDatabaseCommand;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Tests\File;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RefreshDatabaseCommandTest extends TestCase
{
    private ManagerInterface $manager;
    private Repository $repository;
    private ManagerRegistry $registry;
    private RefreshDatabaseCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(ManagerInterface::class);
        $this->manager->method('getClass')->willReturn(File::class);
        $this->repository = $this->createMock(Repository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->command = new RefreshDatabaseCommand($this->manager, $this->registry, $this->repository);
    }

    public function testExecute()
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->once())->method('flush');
        $emMock->expects($this->once())->method('clear');

        $this->manager->expects($this->once())->method('getClass');

        $this->registry->expects($this->once())->method('getManagerForClass')->with(File::class)->willReturn($emMock);

        $file1 = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file1->setId(1);

        $file2 = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2->setId(2);
        $this->repository->expects($this->once())->method('findAllForBatchProcessing')->willReturn([$file1, $file2]);

        $this->manager
            ->expects($this->exactly(2))
            ->method('refresh')->withConsecutive(
                [$this->identicalTo($file1)],
                [$this->identicalTo($file2)]
            );

        $commandTester = new CommandTester($this->command);
        $this->assertSame(0, $commandTester->execute([]));
    }

    public function testManagerNotFound()
    {
        $this->manager->expects($this->once())->method('getClass');
        $this->registry->expects($this->once())->method('getManagerForClass')->with(File::class)->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No manager found for '.File::class);

        $commandTester = new CommandTester($this->command);
        $this->assertSame(0, $commandTester->execute([]));
    }

    public function testExactBatchSize()
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->exactly(3))->method('flush');
        $emMock->expects($this->exactly(3))->method('clear');

        $this->registry->expects($this->once())->method('getManagerForClass')->with(File::class)->willReturn($emMock);

        $files = [];

        for ($i = 0; $i < 40; $i++) {
            $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
            $file->setId($i);
            $files[] = $file;
        }

        $this->repository->expects($this->once())->method('findAllForBatchProcessing')->willReturn($files);

        $commandTester = new CommandTester($this->command);
        $this->assertSame(0, $commandTester->execute([]));
    }

    public function testExactMinusOneBatchSize()
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->expects($this->exactly(3))->method('flush');
        $emMock->expects($this->exactly(3))->method('clear');

        $this->registry->expects($this->once())->method('getManagerForClass')->with(File::class)->willReturn($emMock);

        $files = [];

        for ($i = 0; $i < 59; $i++) {
            $file = new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
            $file->setId($i);
            $files[] = $file;
        }

        $this->repository->expects($this->once())->method('findAllForBatchProcessing')->willReturn($files);

        $commandTester = new CommandTester($this->command);
        $this->assertSame(0, $commandTester->execute([]));
    }
}