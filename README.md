# Files

[![Build Status](https://travis-ci.org/Warxcell/files.svg?branch=master)](https://travis-ci.org/Warxcell/files)

[![codecov](https://codecov.io/gh/Warxcell/files/branch/master/graph/badge.svg)](https://codecov.io/gh/Warxcell/files)

Dependency Matrix:

Versions 1.X.X uses FlySystem ^1.0   
Versions 2.X.X uses FlySystem ^2.0

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
        class: League\Flysystem\Adapter\Local
        arguments:
            - "/directory/for/files/"
    League\Flysystem\Filesystem:
        arguments:
            - "@files_local_adapter"

    Arxy\FilesBundle\Twig\FilesExtension:
        tags:
            - { name: twig.extension }

    Arxy\FilesBundle\NamingStrategy\IdToPathStrategy: ~
    Arxy\FilesBundle\NamingStrategy\AppendExtensionStrategy:
        arguments:
            - '@Arxy\FilesBundle\NamingStrategy\IdToPathStrategy'

    Arxy\FilesBundle\NamingStrategy:
        alias: Arxy\FilesBundle\NamingStrategy\AppendExtensionStrategy

    Arxy\FilesBundle\Manager:
        arguments: 
            $class: "App\\Entity\\File"

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


## Serving local files

There are 2 possibilities to serve files: Either app will serve files with controller, which allow fine-control of ACL for example.
Either configure symlink of upload path to web directory.

1. Serving with Controller

Create controller which will serve files.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use Arxy\FilesBundle\Manager;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Adapter\Local;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileController extends AbstractController
{
    public function download($id, Manager $fileManager, Local $localAdapter, EntityManagerInterface $em)
    {
        /** @var FileEntity $file */
        $file = $em->getRepository(File::class)->findOneBy(
            [
                'md5Hash' => $id,
            ]
        );

        if ($file === null) {
            throw $this->createNotFoundException('File not found');
        }

        $response = $this->file(
            $localAdapter->applyPathPrefix($fileManager->getPathname($file)),
            $file->getOriginalFilename(),
            ResponseHeaderBag::DISPOSITION_INLINE
        );
        $response->headers->set('Content-Type', $file->getMimeType());
        $response->setLastModified($file->getCreatedAt());
        $response->setPublic();

        $now = new \DateTimeImmutable();
        $expireAt = $now->modify("+30 days");
        $response->setExpires($expireAt);

        return $response;
    }
}
```
Register route

```yaml
file_download:
    path: /file/{id}
    controller: App\Controller\FileController::download
```

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

## Usage with <a href="https://api-platform.com/">API Platform</a>:

```php
<?php

declare(strict_types=1);

namespace App\Controller\ApiPlatform;

use Arxy\FilesBundle\Manager;
use Arxy\FilesBundle\Model\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class Upload
{
    private Manager $fileManager;

    public function __construct(Manager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function __invoke(Request $request): File
    {
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        return $this->fileManager->upload($uploadedFile);
    }
}
```


```php
<?php
declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApiPlatform\Upload;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="files")
 * @ApiResource(
 *     iri="http://schema.org/MediaObject",
 *     normalizationContext={
 *         "groups"={"file_read"}
 *     },
 *     collectionOperations={
 *         "post"={
 *             "controller"=Upload::class,
 *             "deserialize"=false,
 *             "validation_groups"={"Default"},
 *             "openapi_context"={
 *                 "requestBody"={
 *                     "content"={
 *                         "multipart/form-data"={
 *                             "schema"={
 *                                 "type"="object",
 *                                 "properties"={
 *                                     "file"={
 *                                         "type"="string",
 *                                         "format"="binary"
 *                                     }
 *                                 }
 *                             }
 *                         }
 *                     }
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get"
 *     }
 * )
 */
class File extends \Arxy\FilesBundle\Entity\File
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue
     * @Groups({"file_read"})
     */
    protected $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}

```

Serving depends from how you want to serve it. You might want to use LiipImagineBundle as mention above, or CDN solution.

If you want to use it with own image hosting and LiipImagineBundle, you probably could add something like that:

```php
    /**
     * @var array
     * @Groups({"file_read"})
     */
    private $formats = [];

    public function getFormats(): array
    {
        return $this->formats;
    }

    public function setFormats(array $formats): void
    {
        $this->formats = $formats;
    }
```

and fill these from Event Listener or ORM Listener or Serializer Normalizer.

here is example with Serializer Normalizer:

```php
<?php
declare(strict_types=1);

namespace App\Serializer;

use App\Entity\File;
use App\Service\ImageHelper;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class FileNormalizer implements NormalizerInterface
{
    /** @var ObjectNormalizer */
    private $objectNormalizer;

    /** @var ImageHelper */
    private $imageHelper;

    /** @var FilterConfiguration */
    private $filterConfiguration;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        ImageHelper $imageHelper,
        FilterConfiguration $filterConfiguration
    ) {
        $this->objectNormalizer = $objectNormalizer;
        $this->imageHelper = $imageHelper;
        $this->filterConfiguration = $filterConfiguration;
    }

    public function normalize($object, $format = null, array $context = array())
    {
        /** @var File $object */
        $data = $this->objectNormalizer->normalize($object, $format, $context);

        $data['formats'] = array_reduce(
            array_keys($this->filterConfiguration->all()),
            function ($array, $filter) use ($object) {
                $array[$filter] = $this->imageHelper->getUrl($object, $filter);

                return $array;
            },
            []
        );

        return $data;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $format === 'json' && $data instanceof File;
    }
}
```

You will receive following json as response:

```json
{
   "id":145,
   "formats":{
      "squared_thumbnail":"https:\/\/host.com\/media\/cache\/resolve\/squared_thumbnail\/1\/4\/5\/145"
   }
}
```

## Migrating between naming strategy.

First configure the new naming strategy, but keep the old one as service.
Then register the command for migration:
```yaml
services:
    Arxy\FilesBundle\Command\MigrateNamingStrategyCommand:
        arguments:
            $oldNamingStrategy: 'old_naming_strategy_service_id'
```

then run it. 
```shell script
bin/console arxy:files:migrate-naming-strategy
```

Please note that until files are migrated - if some file is requested - it will throw error.


