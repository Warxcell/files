<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional;

use Arxy\FilesBundle\ManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class AbstractFunctionalTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected ManagerInterface $manager;
    protected FilesystemOperator $flysystem;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->buildDb($kernel);

        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        $this->manager = static::getContainer()->get('public');
        $this->flysystem = static::getContainer()->get('in_memory');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        unset($this->entityManager);

        $this->manager->clear();
        unset($this->manager);

        unset($this->flysystem);
    }

    protected static function getKernelClass()
    {
        return Kernel::class;
    }

    protected static function createKernel(array $options = [])
    {
        $kernel = parent::createKernel($options);
        assert($kernel instanceof Kernel);
        $kernel->config(static::getConfig());
        $kernel->bundles(static::getBundles());
        $kernel->setTestCase(static::class);

        return $kernel;
    }

    abstract protected static function getConfig(): string;

    abstract protected static function getBundles(): array;

    private function buildDb($kernel): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $application->run(
            new ArrayInput(
                [
                    'doctrine:schema:create',
                ]
            ),
            new ConsoleOutput()
        );
    }
}
