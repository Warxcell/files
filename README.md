# Files

[![Build Status](https://travis-ci.org/Warxcell/files.svg?branch=master)](https://travis-ci.org/Warxcell/files)

[![codecov](https://codecov.io/gh/Warxcell/files/branch/master/graph/badge.svg)](https://codecov.io/gh/Warxcell/files)

Dependency Matrix:

Versions 1.X.X uses FlySystem ^1.0 (not maintained)  
Versions 2.X.X uses FlySystem ^2.0

## Provides easy file management (with persistence layer for metadata).

### Main Features

- Uses FlySystem for File management (this allows you to use existing adapters to save files anywhere)
- Persist file information in database
- Uses checksums (md5) to prevent double upload (thus saving space). If same file is found - it's reused
- Automatic hooks that manages the files (on entity persist file will be uploaded, on entity remove - file will be
  removed)
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
        class: League\Flysystem\Local\LocalFilesystemAdapter
        arguments:
            - "/directory/for/files/"

    League\Flysystem\Filesystem:
        arguments:
            - "@files_local_adapter"

    League\Flysystem\FilesystemOperator:
        alias: League\Flysystem\Filesystem

    Arxy\FilesBundle\Twig\FilesExtension:
        tags:
            - {name: twig.extension}

    Arxy\FilesBundle\NamingStrategy\IdToPathStrategy: ~
    Arxy\FilesBundle\NamingStrategy\AppendExtensionStrategy:
        arguments:
            - '@Arxy\FilesBundle\NamingStrategy\IdToPathStrategy'

    Arxy\FilesBundle\NamingStrategy:
        alias: Arxy\FilesBundle\NamingStrategy\AppendExtensionStrategy

    Arxy\FilesBundle\Manager:
        arguments:
            $class: "App\\Entity\\File"

    Arxy\FilesBundle\ManagerInterface:
        alias: Arxy\FilesBundle\Manager

    Arxy\FilesBundle\EventListener\DoctrineORMListener:
        arguments: ["@Arxy\\FilesBundle\\ManagerInterface"] # This can be omit, if using autowiring.
        tags:
            - {name: doctrine.event_listener, event: 'postPersist'}
            - {name: doctrine.event_listener, event: 'preRemove'}

    Arxy\FilesBundle\Form\Type\FileType:
        arguments: ["@Arxy\\FilesBundle\\ManagerInterface"] # This can be omit, if using autowiring.
        tags: # This can be omit, if using autowiring.
            - {name: form.type}
```

or using pure PHP

```php
$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter;
$filesystem = new \League\Flysystem\Filesystem($adapter);

$namingStrategy = new \Arxy\FilesBundle\NamingStrategy\IdToPathStrategy();

$fileManager = new \Arxy\FilesBundle\Manager(\App\Entity\File::class, DoctrineManagerRegistry, $filesystem, $namingStrategy);
```

## Upload file

```php
$file = new \SplFileInfo($pathname);
$fileEntity = $fileManager->upload($file);
```

Please note that file is not actually moved to its final location until file is persisted into db, which is done by
Listeners. (Arxy\FilesBundle\DoctrineORMListener for example)

Upload using form Type:

```php

$formMapper->add(
    'image',
    FileType::class,
    [
        'required' => false,
        'constraints' => [ConstraintsOnEntity]
        'input_options' => [
            'attr' => [
                'accept' => 'image/*',
            ],
            'constraints' => [
                   SymfonyConstraintsOnFiles
            ]
        ],
    ]
);
```

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

This bundle also contains form and constraint for uploading and validating files. You can write your own naming strategy
how files are created on Filesystem. You can even write your own FileSystem backend for Flysystem and use it here.

Currently only Doctrine ORM is supported as persistence layer. Feel free to submit PRs for others.

## Serving files from controller

1. Serving with Controller

Create the controller which will serve files.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use Arxy\FilesBundle\ManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class FileController extends AbstractController
{
    /**
     * @Route(path="/file/{id}", name="file_download")
     */
    public function download($id, ManagerInterface $fileManager, EntityManagerInterface $em)
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

        $response = new StreamedResponse();

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $file->getOriginalFilename()
        );
        $response->headers->set('Content-Disposition', $disposition);

        $response->headers->set('Content-Type', $file->getMimeType());
        $response->setLastModified($file->getCreatedAt());
        $response->setPublic();
        $response->setEtag($file->getMd5Hash());

        $now = new \DateTimeImmutable();
        $expireAt = $now->modify("+30 days");
        $response->setExpires($expireAt);

        $stream = $fileManager->readStream($file);
        $response->setCallback(
            function () use ($stream) {
                $out = fopen('php://output', 'wb');

                stream_copy_to_stream($stream, $out);

                fclose($out);
                fclose($stream);
            }
        );

        return $response;
    }
}

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
use Arxy\FilesBundle\ManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class ImageHelper
{
    private CacheManager $cacheManager;
    private ManagerInterface $fileManager;

    public function __construct(CacheManager $cacheManager, ManagerInterface $fileManager)
    {
        $this->cacheManager = $cacheManager;
        $this->fileManager = $fileManager;
    }

    public function getUrl(File $file, string $filter)
    {
        return $this->cacheManager->getBrowserPath($this->fileManager->getPathname($file), $filter);
    }
}
```

```php

 $this->imageHelper->getUrl($file, 'squared_thumbnail');
```

## Usage with <a href="https://api-platform.com/">API Platform</a>:

### Uploading

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
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

```

### Serving

Serving depends from how you want to serve it. You might want to use LiipImagineBundle as mention above, or CDN
solution.

#### Directly

If you want directly to serve file with CDN, you can use Path Resolver + Normalizer:

```php
<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\File;
use Arxy\FilesBundle\PathResolver;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class FileNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $objectNormalizer;
    private PathResolver $pathResolver;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        PathResolver $pathResolver
    ) {
        $this->objectNormalizer = $objectNormalizer;
        $this->pathResolver = $pathResolver;
    }

    public function normalize($object, $format = null, array $context = array())
    {
        assert($object instanceof File);
        $data = $this->objectNormalizer->normalize($object, $format, $context);
        $data['url'] = $this->pathResolver->getPath($object);

        return $data;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof File;
    }
}
```

```php
    /**
     * @var string
     * @Groups({"file_read"})
     */
    private string $url = null;

    public function getUrl(): array
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
```

You will receive following json as response:

```json
{
  "id": 145,
  "mimeType": "application/pdf",
  "size": 532423,
  "url": "https://example.com/link-to-image.pdf"
}
```

#### LiipImagineBundle

If you want to use it with LiipImagineBundle, you probably could add something like that:

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
        return $data instanceof File;
    }
}
```

You will receive following json as response:

```json
{
  "id": 145,
  "formats": {
    "squared_thumbnail": "https:\/\/host.com\/media\/cache\/resolve\/squared_thumbnail\/1\/4\/5\/145"
  }
}
```

## Migrating between naming strategy.

First configure the new naming strategy, but keep the old one as service. Then register the command for migration:

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

## PathResolver: used to generate browser URL to access the file. Few built-in resolvers exists:

### AssetsPathResolver:

```yaml
    Arxy\FilesBundle\PathResolver\AssetsPathResolver:
        arguments:
            $manager: '@Arxy\FilesBundle\ManagerInterface'
            $package: 'packageName' # https://symfony.com/doc/current/components/asset.html#asset-packages

    Arxy\FilesBundle\PathResolver:
        alias: Arxy\FilesBundle\PathResolver\AssetsPathResolver
```

### AwsS3PathResolver:

```yaml
    Aws\S3\S3Client:
        class: Aws\S3\S3Client
        arguments:
            $args:
                region: 'region'
                version: 'version'
                credentials:
                    key: 'key'
                    secret: 'secret'

    Aws\S3\S3ClientInterface:
        alias: Aws\S3\S3Client

    Arxy\FilesBundle\PathResolver\AwsS3PathResolver:
        arguments:
            $s3Client: '@Aws\S3\S3ClientInterface'
            $bucket: 'bucket-name'
            $manager: '@Arxy\FilesBundle\ManagerInterface'

    Arxy\FilesBundle\PathResolver:
        alias: Arxy\FilesBundle\PathResolver\AwsS3PathResolver
```

### AzureBlobStoragePathResolver:

```yaml
    MicrosoftAzure\Storage\Blob\BlobRestProxy:
        factory: ['MicrosoftAzure\Storage\Blob\BlobRestProxy', 'createBlobService']
        arguments:
            $connectionString: 'DefaultEndpointsProtocol=https;AccountName=xxxxxxxx;EndpointSuffix=core.windows.net'

    Arxy\FilesBundle\PathResolver\AzureBlobStoragePathResolver:
        arguments:
            $client: '@MicrosoftAzure\Storage\Blob\BlobRestProxy'
            $container: 'container-name'
            $manager: '@Arxy\FilesBundle\ManagerInterface'

    Arxy\FilesBundle\PathResolver:
        alias: Arxy\FilesBundle\PathResolver\AzureBlobStoragePathResolver
```

### AzureBlobStorageSASPathResolver:

- Decorator that accepts `Arxy\FilesBundle\PathResolver\AzureBlobStoragePathResolver` and
  adds <a href="https://docs.microsoft.com/en-us/rest/api/storageservices/create-service-sas">SAS Signature</a>

Create `AzureBlobStorageSASParametersFactory` instance that will be responsible for creating parameters for signature.

```php
class MyFactory implements \Arxy\FilesBundle\PathResolver\AzureBlobStorageSASParametersFactory 
{
    public function create(\Arxy\FilesBundle\Model\File $file) : \Arxy\FilesBundle\PathResolver\AzureBlobStorageSASParameters
    {
        return new \Arxy\FilesBundle\PathResolver\AzureBlobStorageSASParameters(
            new \DateTimeImmutable('+10 minutes'),
        );
    }
}
```

```yaml
    MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper:
        arguments:
            $accountName: 'account-name'
            $accountKey: 'account-key'

    MyFactory: ~

    Arxy\FilesBundle\PathResolver\AzureBlobStorageSASParametersFactory:
        alias: '@MyFactory'

    Arxy\FilesBundle\PathResolver\AzureBlobStorageSASPathResolver:
        arguments:
            $pathResolver: '@Arxy\FilesBundle\PathResolver\AzureBlobStoragePathResolver'
            $signatureHelper: '@MicrosoftAzure\Storage\Blob\BlobSharedAccessSignatureHelper'
            $factory: '@Arxy\FilesBundle\PathResolver\AzureBlobStorageSASParametersFactory'

    Arxy\FilesBundle\PathResolver:
        alias: Arxy\FilesBundle\PathResolver\AzureBlobStorageSASPathResolver
```

### SymfonyCachePathResolver:

Used to cache the result from decorated Path Resolver. Useful for example in conjunction with AwsS3PathResolver, where
to get the path to uploaded file, an API call is made. This resolver will cache the response from AWS S3 servers and
next time you need the file path, it will be returned from cache.
Uses https://symfony.com/doc/current/components/cache.html

```yaml
    Arxy\FilesBundle\PathResolver\AwsS3PathResolver:
        arguments:
            $bucket: '%env(AWS_S3_BUCKET)%'
            $manager: '@Arxy\FilesBundle\ManagerInterface'

    Arxy\FilesBundle\PathResolver\SymfonyCachePathResolver:
        arguments:
            $pathResolver: '@Arxy\FilesBundle\PathResolver\AwsS3PathResolver'
            $cache: '@cache.app'

    Arxy\FilesBundle\PathResolver:
        alias: Arxy\FilesBundle\PathResolver\SymfonyCachePathResolver
```

### DelegatingPathResolver:

Used when your system have multiple file entities:

```yaml
    Arxy\FilesBundle\PathResolver\DelegatingPathResolver:
        arguments:
            $resolvers: {'App\Entity\File': '@resolver'}
```

### You can also combine Manager and PathResolver into one, using PathResolverManager decorator, so you can use singe instance for both operations:

```yaml
    Arxy\FilesBundle\PathResolverManager:
        arguments:
            $manager: '@manager'
            $pathResolver: '@path_resolver'

    Arxy\FilesBundle\ManagerInterface:
        alias: Arxy\FilesBundle\PathResolverManager

    Arxy\FilesBundle\PathResolver:
        alias: Arxy\FilesBundle\PathResolverManager
```

### Sending additional parameters to path resolver.

Ok, we have path generated to our files now, but what if we want to represent same file, differently? Obviously we
cannot do this currently. Let's change that! Let's say we need to enforce different download name.

1. We start by creating decorated file.

```php
class VirtualFile extends \Arxy\FilesBundle\Model\DecoratedFile {

    private ?string $downloadFilename = null;
    
    public function setDownloadFilename(string $filename) {
        $this->downloadFilename = $filename;
    }
    
    public function getDownloadFilename(): ?string {
        return $this->downloadFilename;
    }
}
```

2. Then we create/decorate also the path resolver

```php
class VirtualFilePathResolver implements \Arxy\FilesBundle\PathResolver 
{
    public function getPath(\Arxy\FilesBundle\Model\File $file) : string {
        assert($file instanceof VirtualFile);
        
        return sprintf('url?download_filename=%s', $file->getDownloadFilename());
    }
}
```

3. Then we can use path resolver as usual:
```php
public function someAction(\Arxy\FilesBundle\PathResolver $pathResolver) {
    $virtualFile = new \Arxy\FilesBundle\Tests\VirtualFile($file);
    $virtualFile->setDownloadFilename('this_file_is_renamed_during_download.jpg');
    $downloadUrl = $pathResolver->getPath($virtualFile);
}
```

### Known issues

- If file entity is deleted within transaction and transaction is rolled back - file will be deleted.