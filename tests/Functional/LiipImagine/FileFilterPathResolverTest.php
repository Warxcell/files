<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\LiipImagine;


use Arxy\FilesBundle\LiipImagine\FileFilter;
use Arxy\FilesBundle\LiipImagine\FileFilterPathResolver;
use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Model\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FileFilterPathResolverTest extends KernelTestCase
{
    private File $file;

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
            new NullOutput()
        );
    }

    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->buildDb($kernel);

        $manager = self::$container->get(ManagerInterface::class);
        assert($manager instanceof ManagerInterface);
        $entityManager = self::$container->get(EntityManagerInterface::class);
        assert($entityManager instanceof EntityManagerInterface);

        $this->file = $manager->upload(new \SplFileObject(__DIR__.'/../../files/image1.jpg'));
        $entityManager->persist($this->file);
        $entityManager->flush();
    }

    public function testFilter()
    {
        $pathResolver = self::$container->get(FileFilterPathResolver::class);
        assert($pathResolver instanceof FileFilterPathResolver);

        $path = $pathResolver->getPath(new FileFilter($this->file, 'thumbnail'));
        $this->assertSame(
            'http://localhost/media/cache/resolve/thumbnail/9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1',
            $path
        );
    }

    public function testInvalidInstancePassed()
    {
        $pathResolver = self::$container->get(FileFilterPathResolver::class);
        assert($pathResolver instanceof FileFilterPathResolver);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Arxy\FilesBundle\LiipImagine\FileFilter", "Arxy\FilesBundle\Tests\Functional\Entity\File" given'
        );
        $pathResolver->getPath($this->file);
    }
}