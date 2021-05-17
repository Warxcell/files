<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\EventListener;

use Closure;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractDoctrineORMListener
{
    protected string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    final protected function supports(object $entity): bool
    {
        return $entity instanceof $this->class;
    }

    final protected function handleEmbeddable(
        EntityManagerInterface $entityManager,
        object $entity,
        string $class,
        Closure $action
    ): void {
        $classMetadata = $entityManager->getClassMetadata(ClassUtils::getClass($entity));

        foreach ($classMetadata->embeddedClasses as $property => $embeddedClass) {
            if (!is_a($embeddedClass['class'], $class, true)) {
                continue;
            }

            $refl = new \ReflectionObject($entity);
            $reflProperty = $refl->getProperty($property);
            $reflProperty->setAccessible(true);
            $file = $reflProperty->getValue($entity);

            if ($file === null) {
                continue;
            }
            $action($file);
        }
    }
}