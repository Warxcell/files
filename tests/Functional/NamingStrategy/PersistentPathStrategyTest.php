<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\NamingStrategy;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\Functional\Entity\EmbeddableFilePersistentPath;
use Arxy\FilesBundle\Tests\Functional\Entity\News;
use SplFileObject;

class PersistentPathStrategyTest extends AbstractStrategyTest
{
    protected static function getConfig(): string
    {
        return __DIR__ . '/PersistPathStrategy/config.yml';
    }

    protected static function getBundles(): array
    {
        return [];
    }

    public function testEmbeddable(): void
    {
        /** @var ManagerInterface<EmbeddableFilePersistentPath> $manager */
        $manager = self::getContainer()->get('embeddable');
        $news = new News();

        $news->setEmbeddableFilePersistentPath(
            $manager->upload(new SplFileObject(__DIR__ . '/../../files/image1.jpg'))
        );
        $this->entityManager->persist($news);
        $this->entityManager->flush();

        self::assertTrue($this->doesFileExists($news->getEmbeddableFilePersistentPath()));
    }
}
