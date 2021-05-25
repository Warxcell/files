<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests;

use Arxy\FilesBundle\Migrator;
use Arxy\FilesBundle\NamingStrategy;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    private FilesystemOperator $filesystem;
    private NamingStrategy $oldNamingStrategy;
    private NamingStrategy $newNamingStrategy;
    private Migrator $migrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->createMock(FilesystemOperator::class);

        $this->oldNamingStrategy = new class implements NamingStrategy {
            public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
            {
                return null;
            }

            public function getFileName(\Arxy\FilesBundle\Model\File $file): string
            {
                return 'old_'.$file->getMd5Hash();
            }
        };

        $this->newNamingStrategy = new class implements NamingStrategy {
            public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
            {
                return null;
            }

            public function getFileName(\Arxy\FilesBundle\Model\File $file): string
            {
                return 'new_'.$file->getMd5Hash();
            }
        };

        $this->migrator = new Migrator($this->filesystem, $this->oldNamingStrategy, $this->newNamingStrategy);
    }

    public function testNotMigrated(): void
    {
        $file = new File('image2.jpg', 24053, '9aa1c5fc7c9388166d7ce7fd46648dd1', 'image/jpeg');

        $this->filesystem->expects(self::once())->method('fileExists')->with('old_9aa1c5fc7c9388166d7ce7fd46648dd1')
            ->willReturn(true);

        $this->filesystem->expects(self::once())->method('move')->with(
            'old_9aa1c5fc7c9388166d7ce7fd46648dd1',
            'new_9aa1c5fc7c9388166d7ce7fd46648dd1'
        );

        self::assertTrue($this->migrator->migrate($file));
    }

    public function testMigrated(): void
    {
        $file = new File('image2.jpg', 24053, '9aa1c5fc7c9388166d7ce7fd46648dd1', 'image/jpeg');

        $this->filesystem->expects(self::once())->method('fileExists')->with('old_9aa1c5fc7c9388166d7ce7fd46648dd1')
            ->willReturn(false);

        self::assertFalse($this->migrator->migrate($file));
    }
}
