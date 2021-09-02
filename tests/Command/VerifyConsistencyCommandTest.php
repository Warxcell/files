<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Command;

use Arxy\FilesBundle\Command\VerifyConsistencyCommand;
use Arxy\FilesBundle\FileException;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Storage\FlysystemStorage;
use Arxy\FilesBundle\Tests\File;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function array_map;
use function base64_encode;
use function preg_replace;
use function str_replace;
use function strlen;
use const PHP_EOL;

class VerifyConsistencyCommandTest extends TestCase
{
    private ManagerInterface $manager;
    private Repository $repository;
    private FilesystemReader $flysystem;
    private VerifyConsistencyCommand $command;

    public function testExecute(): void
    {
        $file1 = new File('filename.txt', 5, '5a105e8b9d40e1329780d62ea2265d8a', 'text/plain2');
        $file1->setId(1);

        $file2 = new File('filename', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg');
        $file2->setId(2);

        $files = [$file1, $file2];
        $this->repository->expects(self::once())->method('findAllForBatchProcessing')->willReturn($files);

        $this->manager
            ->expects(self::exactly(2))
            ->method('getPathname')
            ->withConsecutive(...array_map(static fn (File $file): array => [self::identicalTo($file)], $files))
            ->will(self::onConsecutiveCalls('file1path', 'file2path'));

        $content = 'test';
        $this->flysystem
            ->expects(self::exactly(2))
            ->method('readStream')
            ->withConsecutive(['file1path'])
            ->will(
                self::onConsecutiveCalls(
                    fopen('data://text/plain;base64,' . base64_encode($content), 'r'),
                    self::throwException(new FileException($file2, 'File not found'))
                )
            );

        $this->flysystem
            ->expects(self::exactly(1))
            ->method('fileSize')
            ->withConsecutive(['file1path'])
            ->will(self::onConsecutiveCalls(strlen($content)));

        $this->flysystem
            ->expects(self::exactly(1))
            ->method('mimeType')
            ->withConsecutive(['file1path'])
            ->will(self::onConsecutiveCalls('text/plain'));

        $commandTester = new CommandTester($this->command);
        self::assertSame(0, $commandTester->execute([]));

        $output = str_replace(PHP_EOL, '', $commandTester->getDisplay());
        $output = preg_replace('/  +/', ' ', $output);

        self::assertStringContainsString('File file1path wrong size! Actual size: 4 bytes, expected 5 bytes!', $output);
        self::assertStringContainsString(
            'File file1path wrong hash! Actual hash: 098f6bcd4621d373cade4e832627b4f6, expected 5a105e8b9d40e1329780d62ea2265d8a!',
            $output
        );
        self::assertStringContainsString(
            'File file1path wrong mimeType! Actual mimeType: text/plain, expected text/plain2!',
            $output
        );
        self::assertStringContainsString('File file2path missing!', $output);
        self::assertStringContainsString('4 errors detected', $output);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(ManagerInterface::class);
        $this->flysystem = $this->createMock(FilesystemOperator::class);
        $this->repository = $this->createMock(Repository::class);

        $this->command = new VerifyConsistencyCommand(
            new FlysystemStorage($this->flysystem),
            $this->manager,
            $this->repository
        );
    }
}
