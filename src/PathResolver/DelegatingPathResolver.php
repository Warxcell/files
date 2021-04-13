<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\PathResolver;

use Arxy\FilesBundle\Model\File;
use Arxy\FilesBundle\PathResolver;
use LogicException;

class DelegatingPathResolver implements PathResolver
{
    /** @var PathResolver[] */
    private array $resolvers;

    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    public function getPath(File $file): string
    {
        return $this->getResolver($file)->getPath($file);
    }

    private function getResolver(File $file): PathResolver
    {
        foreach ($this->resolvers as $class => $resolver) {
            if ($file instanceof $class) {
                return $resolver;
            }
        }

        throw new LogicException('No resolver for '.get_class($file));
    }
}
