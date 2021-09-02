<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Command;

use Arxy\FilesBundle\ErrorHandler;
use Arxy\FilesBundle\FileException;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\MetadataStorage;
use Arxy\FilesBundle\Repository;
use ErrorException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function fclose;
use function hash_algos;
use function in_array;
use function sprintf;

class VerifyConsistencyCommand extends Command
{
    protected static $defaultName = 'arxy:files:verify-consistency';

    private MetadataStorage $storage;
    private ManagerInterface $manager;
    private Repository $repository;
    private string $hashingAlgorithm;

    public function __construct(
        MetadataStorage $storage,
        ManagerInterface $manager,
        Repository $repository,
        string $hashingAlgorithm = 'md5'
    ) {
        if (!in_array($hashingAlgorithm, hash_algos(), true)) {
            throw new InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $hashingAlgorithm));
        }
        parent::__construct();
        $this->storage = $storage;
        $this->manager = $manager;
        $this->repository = $repository;
        $this->hashingAlgorithm = $hashingAlgorithm;
    }

    /**
     * @throws ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $progressBar = $io->createProgressBar();

        $totalErrors = 0;

        $error = function (string $message) use (&$totalErrors, $io): void {
            $totalErrors++;
            $io->error($message);
        };
        $files = $this->repository->findAllForBatchProcessing();
        foreach ($progressBar->iterate($files) as $file) {
            $errors = [];

            $pathname = $this->manager->getPathname($file);

            try {
                $stream = $this->storage->readStream($file, $pathname);
            } catch (FileException $exception) {
                $error(sprintf('File %s missing!', $pathname));
                continue;
            }

            $size = $this->storage->fileSize($file, $pathname);
            if ($file->getSize() !== $size) {
                $error(
                    sprintf(
                        'File %s wrong size! Actual size: %s bytes, expected %s bytes!',
                        $pathname,
                        $size,
                        $file->getSize()
                    )
                );
            }


            $handle = hash_init($this->hashingAlgorithm);
            hash_update_stream($handle, $stream);
            $hash = hash_final($handle);
            ErrorHandler::wrap(static fn (): bool => fclose($stream));

            if ($file->getHash() !== $hash) {
                $error(
                    sprintf(
                        'File %s wrong hash! Actual hash: %s, expected %s!',
                        $pathname,
                        $hash,
                        $file->getHash()
                    )
                );
            }

            $mimeType = $this->storage->mimeType($file, $pathname);
            if ($file->getMimeType() !== $mimeType) {
                $error(
                    sprintf(
                        'File %s wrong mimeType! Actual mimeType: %s, expected %s!',
                        $pathname,
                        $mimeType,
                        $file->getMimeType()
                    )
                );
            }
        }

        if ($totalErrors === 0) {
            $io->success('No inconsistencies detected');
        } else {
            $io->error(sprintf('%s errors detected', $totalErrors));
        }

        return 0;
    }
}
