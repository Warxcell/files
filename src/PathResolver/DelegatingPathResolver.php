<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use LogicException;

/**
 * @implements PathResolver<File>
 */
class DelegatingPathResolver implements PathResolver
{
    /** @var array<class-string<File>, PathResolver<File>> */
    private array $resolvers;

    /**
     * @param array<class-string<File>, PathResolver<File>> $resolvers
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    #[\Override]
    public function getPath(File $file): string
    {
        return $this->getResolver($file)->getPath($file);
    }

    /**
     * @return PathResolver<File>
     * @throws LogicException if no Resolver is found for $file
     */
    private function getResolver(File $file): PathResolver
    {
        $class = get_class($file);
        if (!isset($this->resolvers[$class])) {
            throw new LogicException('No resolver for ' . $class);
        }

        return $this->resolvers[$class];
    }
}
