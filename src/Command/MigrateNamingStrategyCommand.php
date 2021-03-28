<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Repository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateNamingStrategyCommand extends Command
{
    protected static $defaultName = 'arxy:files:migrate-naming-strategy';

    private ManagerInterface $fileManager;
    private ManagerRegistry $registry;
    private Repository $repository;
    private NamingStrategy $oldNamingStrategy;

    public function __construct(
        ManagerInterface $fileManager,
        ManagerRegistry $registry,
        Repository $repository,
        NamingStrategy $oldNamingStrategy
    ) {
        parent::__construct();
        $this->fileManager = $fileManager;
        $this->registry = $registry;
        $this->repository = $repository;
        $this->oldNamingStrategy = $oldNamingStrategy;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $totalMigrated = 0;
        $totalFailed = 0;

        $files = $this->repository->findAllForBatchProcessing();
        foreach ($files as $file) {
            $migrated = $this->fileManager->migrate($file, $this->oldNamingStrategy);
            if ($migrated) {
                $totalMigrated++;
                $io->success('File '.$file->getId().' migrated');
            } else {
                $totalFailed++;
                $io->warning('File '.$file->getId().' not migrated');
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $io->note('Migrated: '.$totalMigrated.'. Failures: '.$totalFailed);
    }
}
