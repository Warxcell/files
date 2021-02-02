<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;

class DelegatingPathResolver implements PathResolver
{
    /** @var PathResolver[] */
    private array $resolvers;

    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    private function getResolver(File $file): PathResolver
    {
        $class = get_class($file);
        if (!isset($this->resolvers[$class])) {
            throw new \LogicException('No resolver for '.$class);
        }

        return $this->resolvers[$class];
    }

    public function getPath(File $file): string
    {
        return $this->getResolver($file)->getPath($file);
    }
}