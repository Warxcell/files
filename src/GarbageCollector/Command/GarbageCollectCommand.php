<?php

declare(strict_types=1);

/*
 * Copyright (C) 2016-2026 Taylor & Hart Limited
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains the property
 * of Taylor & Hart Limited and its suppliers, if any.
 *
 * All   intellectual   and  technical  concepts  contained  herein  are
 * proprietary  to  Taylor & Hart Limited  and  its suppliers and may be
 * covered  by  U.K.  and  foreign  patents, patents in process, and are
 * protected in full by copyright law. Dissemination of this information
 * or  reproduction  of this material is strictly forbidden unless prior
 * written permission is obtained from Taylor & Hart Limited.
 *
 * ANY  REPRODUCTION, MODIFICATION, DISTRIBUTION, PUBLIC PERFORMANCE, OR
 * PUBLIC  DISPLAY  OF  OR  THROUGH  USE OF THIS SOURCE CODE WITHOUT THE
 * EXPRESS  WRITTEN CONSENT OF RARE PINK LIMITED IS STRICTLY PROHIBITED,
 * AND  IN  VIOLATION  OF  APPLICABLE LAWS. THE RECEIPT OR POSSESSION OF
 * THIS  SOURCE CODE AND/OR RELATED INFORMATION DOES NOT CONVEY OR IMPLY
 * ANY  RIGHTS  TO REPRODUCE, DISCLOSE OR DISTRIBUTE ITS CONTENTS, OR TO
 * MANUFACTURE,  USE, OR SELL ANYTHING THAT IT MAY DESCRIBE, IN WHOLE OR
 * IN PART.
 */

namespace Arxy\FilesBundle\GarbageCollector\Command;

use Arxy\FilesBundle\GarbageCollector\EntityReferenceQueryFactory;
use Arxy\FilesBundle\Model\File;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleInvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function implode;
use function in_array;
use function sprintf;

#[AsCommand('arxy:files:garbage-collect')]
final class GarbageCollectCommand extends Command
{
    /**
     * @param list<class-string<File>> $fileClasses
     */
    public function __construct(
        private readonly array $fileClasses,
        private readonly EntityReferenceQueryFactory $entityReferenceQueryFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    /**
     * @throws ConsoleInvalidArgumentException
     */
    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'File entity class to garbage collect')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Remove unreferenced files');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            ['force' => $force, 'file' => $fileClass] = $this->readInput($input);
        } catch (ConsoleInvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        try {
            $fileClasses = $this->resolveFileClasses($fileClass);
        } catch (InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $rows = [];
        $removedCount = 0;

        foreach ($fileClasses as $class) {
            foreach ($this->entityReferenceQueryFactory->findFilesForGarbageCollection($class) as $file) {
                $rows[] = [$class, $file->getHash(), $file->getOriginalFilename()];

                if ($force) {
                    $this->entityManager->remove($file);
                    ++$removedCount;
                }
            }
        }

        if ($rows === []) {
            $io->success('No unreferenced files found.');

            return Command::SUCCESS;
        }

        $io->table(['Entity', 'Hash', 'Original filename'], $rows);

        if ($force) {
            $this->entityManager->flush();
            $io->success(sprintf('Removed %d unreferenced file(s).', $removedCount));
        } else {
            $io->note(sprintf('Found %d unreferenced file(s). Run with --force to remove them.', count($rows)));
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{force: bool, file: string|null}
     *
     * @throws ConsoleInvalidArgumentException
     */
    private function readInput(InputInterface $input): array
    {
        $file = $input->getArgument('file');

        return [
            'force' => (bool) $input->getOption('force'),
            'file' => is_string($file) ? $file : null,
        ];
    }

    /**
     * @return list<class-string<File>>
     *
     * @throws InvalidArgumentException
     */
    private function resolveFileClasses(?string $fileClass): array
    {
        if ($fileClass === null) {
            return $this->fileClasses;
        }

        if (!in_array($fileClass, $this->fileClasses, true)) {
            throw new InvalidArgumentException(sprintf(
                'File entity class "%s" is not configured. Available: %s',
                $fileClass,
                implode(', ', $this->fileClasses)
            ));
        }

        return [$fileClass];
    }
}
