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

        return $kernel;
    }

    abstract protected static function getConfig(): string;

    abstract protected static function getBundles(): array;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->buildDb($kernel);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->manager = self::$container->get('public');

        $this->flysystem = self::$container->get('in_memory');
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

    private function buildDb($kernel)
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
