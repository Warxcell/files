<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Model;

use DateTimeImmutable;

/**
 * @template T of File
 */
abstract class DecoratedFile implements File
{
    /**
     * @var T
     */
    protected File $decorated;

    /**
     * @param T $decorated
     */
    public function __construct(File $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @return T
     */
    public function getDecorated(): File
    {
        return $this->decorated;
    }

    public function getOriginalFilename(): string
    {
        return $this->decorated->getOriginalFilename();
    }

    public function getSize(): int
    {
        return $this->decorated->getSize();
    }

    public function getHash(): string
    {
        return $this->decorated->getHash();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->decorated->getCreatedAt();
    }

    public function getMimeType(): string
    {
        return $this->decorated->getMimeType();
    }
}
