<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Repository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RefreshDatabaseCommand extends Command
{
    protected static $defaultName = 'arxy:files:refresh-database';

    private ManagerInterface $fileManager;
    private ManagerRegistry $registry;
    private Repository $repository;

    public function __construct(ManagerInterface $fileManager, ManagerRegistry $registry, Repository $repository)
    {
        parent::__construct();
        $this->fileManager = $fileManager;
        $this->registry = $registry;
        $this->repository = $repository;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $batchSize = 20;
        $i = 0;

        $objectManager = $this->registry->getManagerForClass($this->fileManager->getClass());
        if ($objectManager === null) {
            throw new \LogicException('No manager found for '.$this->fileManager->getClass());
        }

        $files = $this->repository->findAllForBatchProcessing();
        foreach ($files as $file) {
            $this->fileManager->refresh($file);

            if (($i++ % $batchSize) === 0) {
                $objectManager->flush();
                $objectManager->clear();
            }

            $progressBar->advance();
        }

        $objectManager->flush();
        $objectManager->clear();

        $progressBar->finish();
    }
}
