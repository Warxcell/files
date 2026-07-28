<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\FileException;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\MigratorInterface;
use Arxy\FilesBundle\Repository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('arxy:files:migrate-naming-strategy')]
class MigrateNamingStrategyCommand extends Command
{
    /**
     * @param Repository<File> $repository
     */
    public function __construct(
        private readonly MigratorInterface $migrator,
        private readonly Repository $repository
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $totalMigrated = 0;
        $totalFailed = 0;

        $files = $this->repository->findAllForBatchProcessing();
        foreach ($progressBar->iterate($files) as $file) {
            try {
                $migrated = $this->migrator->migrate($file);
            } catch (FileException $e) {
                $io->error('Error migrating file "' . $file->getHash() . '": ' . $e->getMessage());

                $totalFailed++;
                continue;
            }
            if ($migrated) {
                $totalMigrated++;
                $io->success('File ' . $file->getHash() . ' migrated');
            } else {
                $totalFailed++;
                $io->warning('File ' . $file->getHash() . ' not migrated');
            }
        }

        $io->note('Migrated: ' . (string)$totalMigrated . '. Failures: ' . (string)$totalFailed . '.');

        if ($totalFailed > 0) {
            return 1;
        }

        return 0;
    }
}
