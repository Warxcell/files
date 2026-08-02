<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use Arxy\FilesBundle\UnableToResolvePath;
use LogicException;

/**
 * @template T of File
 * @implements PathResolver<T>
 */
class DelegatingPathResolver implements PathResolver
{
    /** @var array<class-string<T>, PathResolver<T>> */
    private array $resolvers;

    /**
     * @param array<class-string<T>, PathResolver<T>> $resolvers
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    #[\Override]
    public function getPath(File $file): string
    {
        try {
            return $this->getResolver($file)->getPath($file);
        } catch (LogicException $exception) {
            throw new UnableToResolvePath(
                file: $file,
                message: 'No resolver for ' . get_class($file),
                previous: $exception
            );
        }
    }

    /**
     * @param T $file
     * @return PathResolver<T>
     * @throws LogicException
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
