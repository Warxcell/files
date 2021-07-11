<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Repository;
use League\Flysystem\FilesystemReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function sprintf;

class VerifyConsistencyCommand extends Command
{
    protected static $defaultName = 'arxy:files:verify-consistency';

    private FilesystemReader $flysystem;
    private ManagerInterface $manager;
    private Repository $repository;

    public function __construct(
        FilesystemReader $flysystem,
        ManagerInterface $manager,
        Repository $repository
    ) {
        parent::__construct();
        $this->flysystem = $flysystem;
        $this->manager = $manager;
        $this->repository = $repository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $missing = 0;
        $files = $this->repository->findAllForBatchProcessing();
        foreach ($files as $file) {
            $pathname = $this->manager->getPathname($file);
            if (!$this->flysystem->fileExists($pathname)) {
                $missing++;
                $io->error(sprintf('File %s missing!', $pathname));
            }
            $progressBar->advance();
        }

        $progressBar->finish();

        if ($missing === 0) {
            $io->success('No missing files');
        } else {
            $io->error(sprintf('%s files missing', $missing));
        }

        return 0;
    }
}
