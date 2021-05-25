<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Command;

use Arxy\FilesBundle\Command\MigrateNamingStrategyCommand;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\MigratorInterface;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Tests\File;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateNamingStrategyCommandTest extends TestCase
{
    private Repository $repository;
    private MigratorInterface $migrator;
    private MigrateNamingStrategyCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(ManagerInterface::class);
        $this->repository = $this->createMock(Repository::class);
        $this->migrator = $this->createMock(MigratorInterface::class);

        $this->command = new MigrateNamingStrategyCommand($this->migrator, $this->repository);
    }

    public function testExecute()
    {
        $file1 = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file1->setId(1);

        $file2 = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2->setId(2);
        $this->repository->expects($this->once())->method('findAllForBatchProcessing')->willReturn([$file1, $file2]);

        $this->migrator
            ->expects($this->exactly(2))
            ->method('migrate')->withConsecutive(
                [$this->identicalTo($file1)],
                [$this->identicalTo($file2)]
            )
            ->will($this->onConsecutiveCalls(true, false));

        $commandTester = new CommandTester($this->command);
        self::assertSame(0, $commandTester->execute([]));

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('File 098f6bcd4621d373cade4e832627b4f6 migrated', $output);
        self::assertStringContainsString('File 098f6bcd4621d373cade4e832627b4f6 not migrated', $output);
        self::assertStringContainsString('Migrated: 1. Failures: 1.', $output);
    }
}
