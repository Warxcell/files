<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\MigratorInterface;
use Arxy\FilesBundle\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateNamingStrategyCommand extends Command
{
    protected static $defaultName = 'arxy:files:migrate-naming-strategy';

    private MigratorInterface $migrator;
    private Repository $repository;

    public function __construct(
        MigratorInterface $migrator,
        Repository $repository
    ) {
        parent::__construct();
        $this->migrator = $migrator;
        $this->repository = $repository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $totalMigrated = 0;
        $totalFailed = 0;

        $files = $this->repository->findAllForBatchProcessing();
        foreach ($files as $file) {
            $migrated = $this->migrator->migrate($file);
            if ($migrated) {
                $totalMigrated++;
                $io->success('File '.$file->getHash().' migrated');
            } else {
                $totalFailed++;
                $io->warning('File '.$file->getHash().' not migrated');
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->note('Migrated: '.$totalMigrated.'. Failures: '.$totalFailed.'.');

        return 0;
    }
}
