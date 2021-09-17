<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Twig;

use Arxy\FilesBundle\Tests\Functional\AbstractFunctionalTest;
use SplFileObject;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Twig\Environment;

use function md5_file;

class PathResolverTest extends AbstractFunctionalTest
{
    protected static function getConfig(): string
    {
        return __DIR__ . '/path_resolver_config.yml';
    }

    protected static function getBundles(): array
    {
        return [new TwigBundle()];
    }

    public function testPath(): void
    {
        $pathname = __DIR__ . '/../../files/lorem-ipsum.txt';
        $file = $this->manager->upload(new SplFileObject($pathname));

        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);
        $formatted = $twig->render($twig->createTemplate('{{ file_path(file) }}'), ['file' => $file]);

        self::assertSame(md5_file($pathname), $formatted);
    }
}
