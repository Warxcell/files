<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Twig;

use Arxy\FilesBundle\ManagerInterface;
use Arxy\FilesBundle\Tests\File;
use Arxy\FilesBundle\Twig\FilesExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class FilesExtensionTest extends TestCase
{
    private ManagerInterface $manager;
    private FilesExtension $extension;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->extension = new FilesExtension($this->manager);
    }

    public function testGetFilters(): void
    {
        $twigFilters = $this->extension->getFilters();
        self::assertCount(2, $twigFilters);

        self::assertInstanceOf(TwigFilter::class, $twigFilters[0]);
        self::assertSame('format_bytes', $twigFilters[0]->getName());
        self::assertSame($this->extension, $twigFilters[0]->getCallable()[0]);
        self::assertSame('formatBytes', $twigFilters[0]->getCallable()[1]);

        self::assertInstanceOf(TwigFilter::class, $twigFilters[1]);
        self::assertSame('file_content', $twigFilters[1]->getName());
        self::assertSame($this->extension, $twigFilters[1]->getCallable()[0]);
        self::assertSame('readContent', $twigFilters[1]->getCallable()[1]);
    }

    public function testReadContent(): void
    {
        $file = new File('filename', 125, '12345', 'image/jpeg');
        $this->manager->expects($this->once())->method('read')->with($file)->willReturn('all good');

        self::assertSame('all good', $this->extension->readContent($file));
    }

    public function testFormatBytes(): void
    {
        self::assertSame('1.02 kB', $this->extension->formatBytes(1020));
        self::assertSame('1.02 kB', $this->extension->formatBytes(1024));
        self::assertSame('1.52 kB', $this->extension->formatBytes(1524));
        self::assertSame('1.05 MB', $this->extension->formatBytes(1048576));
        self::assertSame('1.07 GB', $this->extension->formatBytes(1073741824));
        self::assertSame('1.10 TB', $this->extension->formatBytes(1099511627776));
    }
}
