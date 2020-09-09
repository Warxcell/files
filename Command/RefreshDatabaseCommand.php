<?php
declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\Model\File;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshDatabaseCommand extends Command
{
    protected static $defaultName = 'arxy:files:refresh-database';

    private Manager $fileManager;
    private ManagerRegistry $doctrine;

    public function __construct(Manager $fileManager, ManagerRegistry $registry)
    {
        parent::__construct();
        $this->fileManager = $fileManager;
        $this->doctrine = $registry;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $progressBar = new ProgressBar($output);

        $batchSize = 20;
        $i = 1;

        $em = $this->doctrine->getManagerForClass($this->fileManager->getClass());

        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('file')->from($this->fileManager->getClass(), 'file');

        $iterableResult = $queryBuilder->getQuery()->iterate();
        foreach ($iterableResult as $row) {
            /** @var File $file */
            $file = $row[0];

            $this->fileManager->refresh($file);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            ++$i;

            $progressBar->advance();
        }

        $em->flush();

        $progressBar->finish();
    }
}