<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional\Twig;

use Arxy\FilesBundle\Tests\Functional\AbstractFunctionalTest;
use SplFileObject;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Twig\Environment;

use function file_get_contents;

class FilesExtensionTest extends AbstractFunctionalTest
{
    protected static function getConfig(): string
    {
        return __DIR__ . '/../config.yml';
    }

    protected static function getBundles(): array
    {
        return [new TwigBundle()];
    }

    public function formatBytesProvider(): iterable
    {
        yield [5, '5 B'];
        yield [1024, '1.02 kB'];
        yield [10000, '10.00 kB'];
        yield [100000, '100.00 kB'];
        yield [1000000, '1.00 MB'];
        yield [1000000000, '1.00 GB'];
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @dataProvider formatBytesProvider
     */
    public function testFormatBytes(int $bytes, string $expected): void
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);
        $formatted = $twig->render($twig->createTemplate('{{ bytes|format_bytes }}'), ['bytes' => $bytes]);

        self::assertEquals($expected, $formatted);
    }

    public function testGetContent(): void
    {
        $pathname = __DIR__ . '/../../files/lorem-ipsum.txt';
        $file = $this->manager->upload(new SplFileObject($pathname));

        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);
        $formatted = $twig->render($twig->createTemplate('{{ file|file_content }}'), ['file' => $file]);
        self::assertSame(file_get_contents($pathname), $formatted);
    }
}
