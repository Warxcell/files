<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateNamingStrategyCommand extends Command
{
    protected static $defaultName = 'arxy:files:migrate-naming-strategy';

    private ManagerInterface $fileManager;
    private ManagerRegistry $doctrine;
    private NamingStrategy $oldNamingStrategy;

    public function __construct(
        ManagerInterface $fileManager,
        ManagerRegistry $registry,
        NamingStrategy $oldNamingStrategy
    ) {
        parent::__construct();
        $this->fileManager = $fileManager;
        $this->doctrine = $registry;
        $this->oldNamingStrategy = $oldNamingStrategy;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $em = $this->doctrine->getManagerForClass($this->fileManager->getClass());

        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('file')->from($this->fileManager->getClass(), 'file');

        $totalMigrated = 0;
        $totalFailed = 0;

        $iterableResult = $queryBuilder->getQuery()->iterate();
        foreach ($iterableResult as $row) {
            /** @var File $file */
            $file = $row[0];

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