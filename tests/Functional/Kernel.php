<?php

declare(strict_types=1);

namespace Arxy\FilesBundle\Tests\Functional;

use Arxy\FilesBundle\ArxyFilesBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyBaseKernel;

class Kernel extends SymfonyBaseKernel
{
    private string $config;
    private array $additionalBundles;

    public function config(string $config)
    {
        $this->config = realpath($config);
    }

    public function bundles(array $bundles)
    {
        $this->additionalBundles = $bundles;
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    private function getVarDir()
    {
        return sys_get_temp_dir().'/'.md5($this->config);
    }

    public function getCacheDir()
    {
        return $this->getVarDir().'/cache';
    }

    public function getLogDir()
    {
        return $this->getVarDir().'/log';
    }

    public function registerBundles(): array
    {
        return array_merge(
            [
                new FrameworkBundle(),
                new DoctrineBundle(),
                new ArxyFilesBundle(),
                new FlysystemBundle(),
            ],
            $this->additionalBundles
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config_base.yml');
        $loader->load($this->config);
    }
}
