<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\LiipImagine;

use Arxy\FilesBundle\LiipImagine\FileFilter;
use Arxy\FilesBundle\LiipImagine\FileFilterPathResolver;
use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\Tests\Functional\AbstractFunctionalTest;
use Liip\ImagineBundle\LiipImagineBundle;
use SplFileObject;

class FileFilterPathResolverTest extends AbstractFunctionalTest
{
    private File $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = $this->manager->upload(new SplFileObject(__DIR__ . '/../../files/image1.jpg'));
        $this->entityManager->persist($this->file);
        $this->entityManager->flush();
    }

    protected static function getConfig(): string
    {
        return __DIR__ . '/config.yml';
    }

    protected static function getBundles(): array
    {
        return [new LiipImagineBundle()];
    }

    public function testFilter(): void
    {
        $pathResolver = self::getContainer()->get(FileFilterPathResolver::class);
        assert($pathResolver instanceof FileFilterPathResolver);

        $path = $pathResolver->getPath(new FileFilter($this->file, 'thumbnail'));
        self::assertSame(
            'http://localhost/media/cache/resolve/thumbnail/9aa1c5fc/7c938816/6d7ce7fd/46648dd1/9aa1c5fc7c9388166d7ce7fd46648dd1',
            $path
        );
    }
}
