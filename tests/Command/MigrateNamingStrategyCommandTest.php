<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Command;

use Arxy\FilesBundle\Command\MigrateNamingStrategyCommand;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Tests\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateNamingStrategyCommandTest extends TestCase
{
    private ManagerInterface $manager;
    private Repository $repository;
    private NamingStrategy $namingStrategy;
    private MigrateNamingStrategyCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(ManagerInterface::class);
        $this->repository = $this->createMock(Repository::class);
        $this->namingStrategy = $this->createMock(NamingStrategy::class);

        $this->command = new MigrateNamingStrategyCommand($this->manager, $this->repository, $this->namingStrategy);
    }

    public function testExecute()
    {
        $file1 = new File();
        $file1->setId(1);

        $file2 = new File();
        $file2->setId(2);
        $this->repository->expects($this->once())->method('findAllForBatchProcessing')->willReturn([$file1, $file2]);

        $this->manager
            ->expects($this->exactly(2))
            ->method('migrate')->withConsecutive(
                [$this->identicalTo($file1), $this->identicalTo($this->namingStrategy)],
                [$this->identicalTo($file2), $this->identicalTo($this->namingStrategy)]
            )
            ->will($this->onConsecutiveCalls(true, false));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('File 1 migrated', $output);
        $this->assertStringContainsString('File 2 not migrated', $output);
        $this->assertStringContainsString('Migrated: 1. Failures: 1.', $output);
    }
}