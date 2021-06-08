<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\NamingStrategy;

use Arxy\FilesBundle\NamingStrategy;
use Arxy\FilesBundle\Tests\File;

class DirectoryChunkSplitStrategyTest extends AbstractStrategyTest
{
    public function getStrategy(): NamingStrategy
    {
        return new NamingStrategy\DirectoryChunkSplitStrategy(new class implements NamingStrategy {
            public function getDirectoryName(\Arxy\FilesBundle\Model\File $file): ?string
            {
                return null;
            }

            public function getFileName(\Arxy\FilesBundle\Model\File $file): string
            {
                return '098f6bcd4621d373cade4e832627b4f6';
            }
        });
    }

    public function getTestCases(): iterable
    {
        yield new NamingStrategyTestCase(
            new File('original_filename.jpg', 125, '098f6bcd4621d373cade4e832627b4f6', 'image/jpeg'),
            '0/9/8/',
            '098f6bcd4621d373cade4e832627b4f6'
        );
    }
}
