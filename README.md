# Files
## Provides easy file management (with persistence layer for metadata).

### Main Features

- Uses FlySystem for File management (this allows you to use existing adapters to save files anywhere)
- Persist file information in database
- Uses checksums (md5) to prevent double upload (thus saving space). If same file is found - it's reused
- Automatic hooks that manages the files (on entity persist file will be uploaded, on entity remove - file will be removed)
- Different naming strategies for handling files.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class File extends \Arxy\FilesBundle\Entity\File
{
    /**
     * @var int|null
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
```

```yaml
services:
    files_local_adapter:
        public: true
        class: League\Flysystem\Adapter\Local
        arguments:
            - "/directory/for/files/"

    files_filesystem:
        class: League\Flysystem\Filesystem
        arguments:
            - "@files_local_adapter"

    Arxy\FilesBundle\Twig\FilesExtension:
        tags:
            - { name: twig.extension }

    Arxy\FilesBundle\NamingStrategy\IdToPathStrategy: ~

    Arxy\FilesBundle\Manager:
        arguments: ["App\\Entity\\File","@doctrine", "@files_filesystem", "@Arxy\\FilesBundle\\NamingStrategy\\IdToPathStrategy"]

    Arxy\FilesBundle\EventListener\DoctrineORMListener:
        arguments: ["@Arxy\\FilesBundle\\Manager"]
        tags:
            - { name: doctrine.event_subscriber }

    Arxy\FilesBundle\Form\Type\FileType:
        arguments: ["@Arxy\\FilesBundle\\Manager"]
        tags:
            - { name: form.type }
```

or using pure PHP

```php
$adapter = new \League\Flysystem\Adapter\Local;
$filesystem = new \League\Flysystem\Filesystem($adapter);

$namingStrategy = new \Arxy\FilesBundle\NamingStrategy\IdToPathStrategy();

$fileManager = new \Arxy\FilesBundle\Manager(\App\Entity\File::class, DoctrineManagerRegistry, $filesystem, $namingStrategy);
```


## Upload file
```php
$file = new \SplFileInfo($pathname);
$fileEntity = $fileManager->upload($file);
```

Please note that file is not actually moved to its final location until file is persisted into db, which is done by Listeners. (Arxy\FilesBundle\DoctrineORMListener for example)

## Read file content
```php
$file = $em->find(File::class, 1);

$content = $fileManager->read($file);
```

## Read stream
```php
$file = $em->find(File::class, 1);

$fileHandle = $fileManager->readStream($file);
```

This bundle also contains form and constraint for uploading and validating files.
You can write your own naming strategy how files are created on Filesystem.
You can even write your own FileSystem backend for Flysystem and use it here.

Currently only Doctrine ORM is supported as persistence layer. Feel free to submit PRs for others.


## usage with <a href="https://github.com/liip/LiipImagineBundle">LiipImagineBundle</a> for image processing.


```yaml
liip_imagine:
    loaders:
        arxy_file_loader:
            flysystem:
                filesystem_service: files_filesystem
    data_loader: arxy_file_loader
    filter_sets:
            # an example thumbnail transformation definition
            # https://symfony.com/doc/current/bundles/LiipImagineBundle/basic-usage.html#create-thumbnails
            squared_thumbnail:
                jpeg_quality:          85
                png_compression_level: 8
                filters:
                    auto_rotate: ~
                    strip: ~
                    thumbnail:
                        size:          [253, 253]
                        mode:          outbound
                        allow_upscale: false
```

```php
<?php

namespace App\Service;

use App\Entity\File;
use Arxy\FilesBundle\Manager;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class ImageHelper
{
    /** @var CacheManager */
    private $cacheManager;

    /** @var Manager */
    private $fileManager;

    public function __construct(CacheManager $cacheManager, Manager $fileManager)
    {
        $this->cacheManager = $cacheManager;
        $this->fileManager = $fileManager;
    }

    public function getUrl(File $file, string $mode)
    {
        return $this->cacheManager->getBrowserPath($this->fileManager->getPathname($file), $mode);
    }
}
```

```php

 $this->imageHelper->getUrl($file, 'squared_thumbnail');
```