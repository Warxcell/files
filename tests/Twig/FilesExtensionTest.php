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

    public function testGetFilters()
    {
        $twigFilters = $this->extension->getFilters();
        $this->assertCount(2, $twigFilters);

        $this->assertInstanceOf(TwigFilter::class, $twigFilters[0]);
        $this->assertSame('format_bytes', $twigFilters[0]->getName());
        $this->assertSame($this->extension, $twigFilters[0]->getCallable()[0]);
        $this->assertSame('formatBytes', $twigFilters[0]->getCallable()[1]);

        $this->assertInstanceOf(TwigFilter::class, $twigFilters[1]);
        $this->assertSame('file_content', $twigFilters[1]->getName());
        $this->assertSame($this->extension, $twigFilters[1]->getCallable()[0]);
        $this->assertSame('readContent', $twigFilters[1]->getCallable()[1]);
    }

    public function testReadContent()
    {
        $file = new File();
        $this->manager->expects($this->once())->method('read')->with($file)->willReturn('all good');

        $this->assertSame('all good', $this->extension->readContent($file));
    }

    public function testFormatBytes()
    {
        $this->assertSame('1020 B', $this->extension->formatBytes(1020));
        $this->assertSame('1 KiB', $this->extension->formatBytes(1024));
        $this->assertSame('1.49 KiB', $this->extension->formatBytes(1524));
        $this->assertSame('1 MiB', $this->extension->formatBytes(1048576));
        $this->assertSame('1 GB', $this->extension->formatBytes(1073741824));
        $this->assertSame('1 TB', $this->extension->formatBytes(1099511627776));
    }
}
