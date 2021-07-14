<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use LogicException;

class DelegatingPathResolver implements PathResolver
{
    /** @var array<class-string<File>, PathResolver> */
    private array $resolvers;

    /**
     * @param array<class-string<File>, PathResolver> $resolvers
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function getPath(File $file): string
    {
        return $this->getResolver($file)->getPath($file);
    }

    /**
     * @template T of File
     * @param T $file
     * @return PathResolver<T>
     * @throws LogicException if no Resolver is found for $file
     */
    private function getResolver(File $file): PathResolver
    {
        $class = get_class($file);
        if (!isset($this->resolvers[$class])) {
            throw new LogicException('No resolver for '.$class);
        }

        return $this->resolvers[$class];
    }
}
