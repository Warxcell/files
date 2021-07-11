<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Command;

use Arxy\FilesBundle\Command\VerifyConsistencyCommand;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Repository;
use Arxy\FilesBundle\Tests\File;
use League\Flysystem\FilesystemReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function array_map;
use function base64_encode;
use function strlen;

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

        $this->flysystem
            ->expects(self::exactly(2))
            ->method('fileExists')
            ->withConsecutive(['file1path'], ['file2path'])
            ->will(self::onConsecutiveCalls(true, false));

        $content = 'test';
        $this->flysystem
            ->expects(self::exactly(1))
            ->method('readStream')
            ->withConsecutive(['file1path'])
            ->will(self::onConsecutiveCalls(fopen('data://text/plain;base64,'.base64_encode($content), 'r')));

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
        self::assertSame(0, $commandTester->execute([], ['decorated' => true]));

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('File file1path wrong size! Actual size: 4 bytes, expected 5 bytes!', $output);
        self::assertStringContainsString(
            'File file1path wrong hash! Actual hash: 098f6bcd4621d373cade4e832627b4f6, expected',
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
        $this->flysystem = $this->createMock(FilesystemReader::class);
        $this->repository = $this->createMock(Repository::class);

        $this->command = new VerifyConsistencyCommand($this->flysystem, $this->manager, $this->repository);
    }
}
