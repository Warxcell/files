<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\ErrorHandler;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Repository;
use InvalidArgumentException;
use League\Flysystem\FilesystemReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function fclose;
use function hash_algos;
use function in_array;
use function sprintf;
use function stream_get_contents;
use function var_dump;

class VerifyConsistencyCommand extends Command
{
    protected static $defaultName = 'arxy:files:verify-consistency';

    private FilesystemReader $flysystem;
    private ManagerInterface $manager;
    private Repository $repository;
    private string $hashingAlgorithm;

    public function __construct(
        FilesystemReader $flysystem,
        ManagerInterface $manager,
        Repository $repository,
        string $hashingAlgorithm = 'md5'
    ) {
        if (!in_array($hashingAlgorithm, hash_algos(), true)) {
            throw new InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $hashingAlgorithm));
        }
        parent::__construct();
        $this->flysystem = $flysystem;
        $this->manager = $manager;
        $this->repository = $repository;
        $this->hashingAlgorithm = $hashingAlgorithm;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $totalErrors = 0;
        $files = $this->repository->findAllForBatchProcessing();
        foreach ($files as $file) {
            $errors = [];

            $pathname = $this->manager->getPathname($file);
            if (!$this->flysystem->fileExists($pathname)) {
                $errors[] = sprintf('File %s missing!', $pathname);
            } else {
                $size = $this->flysystem->fileSize($pathname);
                if ($file->getSize() !== $size) {
                    $errors[] = sprintf(
                        'File %s wrong size! Actual size: %s bytes, expected %s bytes!',
                        $pathname,
                        $size,
                        $file->getSize()
                    );
                }

                $stream = $this->flysystem->readStream($pathname);

                $handle = hash_init($this->hashingAlgorithm);
                hash_update_stream($handle, $stream);
                $hash = hash_final($handle);
                ErrorHandler::wrap(static fn (): bool => fclose($stream));

                if ($file->getHash() !== $hash) {
                    $errors[] = sprintf(
                        'File %s wrong hash! Actual hash: %s, expected %s!',
                        $pathname,
                        $hash,
                        $file->getHash()
                    );
                }

                $mimeType = $this->flysystem->mimeType($pathname);
                if ($file->getMimeType() !== $mimeType) {
                    $errors[] = sprintf(
                        'File %s wrong mimeType! Actual mimeType: %s, expected %s!',
                        $pathname,
                        $mimeType,
                        $file->getMimeType()
                    );
                }
            }

            foreach ($errors as $error) {
                $io->error($error);
                $totalErrors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        if ($totalErrors === 0) {
            $io->success('No inconsistencies detected');
        } else {
            $io->error(sprintf('%s errors detected', $totalErrors));
        }

        return 0;
    }
}
