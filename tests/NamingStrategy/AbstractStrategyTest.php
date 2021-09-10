<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\NamingStrategy;
use PHPUnit\Framework\TestCase;

abstract class AbstractStrategyTest extends TestCase
{
    abstract public function getStrategy(): NamingStrategy;

    /** @return NamingStrategyTestCase[] */
    abstract public function getTestCases(): iterable;

    final public function directoryTestData(): iterable
    {
        foreach ($this->getTestCases() as $testCase) {
            yield [$testCase->getFile(), $testCase->getExpectedDirectoryName()];
        }
    }

    final public function filenameTestData(): iterable
    {
        foreach ($this->getTestCases() as $testCase) {
            yield [$testCase->getFile(), $testCase->getExpectedFilename()];
        }
    }

    /** @dataProvider directoryTestData */
    final public function testDirectoryName(File $file, ?string $expected): void
    {
        self::assertEquals(
            $expected,
            $this->getStrategy()->getDirectoryName($file)
        );
    }

    /** @dataProvider filenameTestData */
    final public function testFilename(File $file, string $expected): void
    {
        self::assertEquals(
            $expected,
            $this->getStrategy()->getFileName($file)
        );
    }
}
