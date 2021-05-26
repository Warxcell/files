# About

Provides easy file management (with persistence layer for metadata).

[![Build Status](https://travis-ci.org/Warxcell/files.svg?branch=master)](https://travis-ci.org/Warxcell/files)

[![codecov](https://codecov.io/gh/Warxcell/files/branch/master/graph/badge.svg)](https://codecov.io/gh/Warxcell/files)

Dependency Matrix:

Versions 1.X.X uses FlySystem ^1.0 (not maintained)  
Versions 2.X.X uses FlySystem ^2.0

- Uses FlySystem for File management (this allows you to use existing adapters to save files anywhere)
- Persist file information in database
- Uses checksums (md5) to prevent double upload (thus saving space). If same file is found - it's reused
- Automatic hooks that manages the files (on entity persist file will be uploaded, on entity remove - file will be
  removed)
- Different naming strategies for handling files.

# Usage

## Configuring

Create the object which will holds the file

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

In case you want to use embeddable instead of Entity for file object:

```php
<?php

namespace App\Entity;

use Arxy\FilesBundle\Model\File;use Doctrine\ORM\Mapping as ORM;
use Arxy\FilesBundle\Entity\EmbeddableFile;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class News
{
    /** @ORM\Embedded(class=EmbeddableFile::class) */
    private ?EmbeddableFile $image = null;
    
    public function getImage(): ?File {
        return $this->image;
    }
    
    public function setImage(?File $file) {
        $this->image = $file;
    }
}
```

Create the repository.

```php
<?php

namespace App\Repository;

use Arxy\FilesBundle\Repository;
use Doctrine\ORM\EntityRepository;
use \Arxy\FilesBundle\Repository\ORM;

class FileRepository extends EntityRepository implements Repository
{
   use ORM;
}
```

```yaml
services:
    Arxy\FilesBundle\NamingStrategy\SplitHashStrategy: ~

flysystem:
    storages:
        in_memory:
            adapter: 'memory'

arxy_files:
    managers:
        public:
            driver: orm
            class: 'App\Entity\File'
            flysystem: 'in_memory'
            naming_strategy: 'Arxy\FilesBundle\NamingStrategy\SplitHashStrategy'
            repository: 'App\Repository\FileRepository'
```

Or using plain services:

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
            $class: 'App\Entity\File'

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

$namingStrategy = new \Arxy\FilesBundle\NamingStrategy\SplitHashStrategy();

$repository = new FileRepository();

$fileManager = new \Arxy\FilesBundle\Manager(\App\Entity\File::class, $filesystem, $namingStrategy, $repository);
```

## Upload file

```php
$file = new \SplFileInfo($pathname);
$fileEntity = $fileManager->upload($file);

$file = $request->files->get('file');
$fileEntity = $fileManager->upload($file);

$entityManager->persist($fileEntity);
$entityManager->flush();
```

In case of embeddable:

```php

$file = new \SplFileInfo($pathname);
$embeddableFile = $fileManager->upload($file);

$news = new \App\Entity\News();
$news->setImage($embeddableFile);

$entityManager->persist($news);
$entityManager->flush();
```

Please note that file is not actually moved to its final location until file is persisted into db, which is done by
Listeners. (Arxy\FilesBundle\DoctrineORMListener for example)

Upload using form Type:

```php

$formBuilder->add(
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
$file = $entityManager->find(File::class, 1);

$content = $fileManager->read($file);
```

## Read stream

```php
$file = $entityManager->find(File::class, 1);

$fileHandle = $fileManager->readStream($file);
```

This bundle also contains form and constraint for uploading and validating files. You can write your own naming strategy
how files are created on Filesystem. You can even write your own FileSystem backend for Flysystem and use it here.

Currently, only Doctrine ORM is supported as persistence layer. Feel free to submit PRs for others.

## Serving files from controller

1. Serving with Controller

Create the controller which will serve files.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Arxy\FilesBundle\Utility\DownloadUtility;

class FileController extends AbstractController
{
    /**
     * @Route(path="/file/{id}", name="file_download")
     */
    public function download(
        $id, 
        EntityManagerInterface $em, 
        DownloadUtility $downloadUtility
    )
    {
        $file = $em->getRepository(File::class)->findOneBy(
            [
                'md5Hash' => $id,
            ]
        );

        if ($file === null) {
            throw $this->createNotFoundException('File not found');
        }

        return $downloadUtility->createResponse($file);
    }
}
```

If you want to force different download name, you can decorate file with `Arxy\FilesBundle\Utility\DownloadableFile`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Arxy\FilesBundle\Utility\DownloadUtility;
use Arxy\FilesBundle\Utility\DownloadableFile;

class FileController extends AbstractController
{
    /**
     * @Route(path="/file/{id}", name="file_download")
     */
    public function download(
        $id,
        EntityManagerInterface $em, 
        DownloadUtility $downloadUtility
    )
    {
        $file = $em->getRepository(File::class)->findOneBy(
            [
                'md5Hash' => $id,
            ]
        );

        if ($file === null) {
            throw $this->createNotFoundException('File not found');
        }

        return $downloadUtility->createResponse(new DownloadableFile($file, 'my_name.jpg', false, new \DateTimeImmutable('date of cache expiry')));
    }
}
```

### Serving

You might want to use LiipImagineBundle or CDN solution, or even controller.

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
use Arxy\FilesBundle\LiipImagine\FileFilter;use Arxy\FilesBundle\LiipImagine\FileFilterPathResolver;use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class FileNormalizer implements NormalizerInterface
{
    private ObjectNormalizer $objectNormalizer;
    private FileFilterPathResolver $fileFilterPathResolver;
    private FilterConfiguration $filterConfiguration;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        FileFilterPathResolver $fileFilterPathResolver,
        FilterConfiguration $filterConfiguration
    ) {
        $this->objectNormalizer = $objectNormalizer;
        $this->fileFilterPathResolver = $fileFilterPathResolver;
        $this->filterConfiguration = $filterConfiguration;
    }

    public function normalize($object, $format = null, array $context = array())
    {
        assert($object instanceof \Arxy\FilesBundle\Model\File);
        $data = $this->objectNormalizer->normalize($object, $format, $context);

        $data['formats'] = array_reduce(
            array_keys($this->filterConfiguration->all()),
            function ($array, $filter) use ($object) {
                $array[$filter] = $this->fileFilterPathResolver->getUrl(new FileFilter($object, $filter));

                return $array;
            },
            []
        );

        return $data;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof \Arxy\FilesBundle\Model\File;
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

## Naming Strategies:

Naming strategy is responsible to converting File object to filepath. Several built-in strategies exists:

### DateStrategy

Use file's createdAt property. Default format: Y/m/d/hash. Example: 2021/05/17/59aeac36ae75786be1b573baad0e77c0

### IdToPathStrategy

Use file's method `getId` and splits it by characters. Expect instanceof `Arxy\FilesBundle\Model\IdentifiableFile`
Example: ID=123456 will result in filepath: `1/2/3/4/5/6/123456`

### SplitHashStrategy

Use file's md5hash and split it into chucks. Example: `098f6bcd4621d373cade4e832627b4f6` will result
in `098f6bcd/4621d373/cade4e83/2627b4f6/098f6bcd4621d373cade4e832627b4f6`

### UuidV5Strategy

Uses UUID V5 to generate hash for file. It consists of namespace (configurable) and value (Uses md5Hash of file).

### AppendExtensionStrategy

Decorator which adds extension of file (.jpg, .pdf, etc).

### DirectoryPrefixStrategy

Decorator which prefixes the generated directory of another naming strategy.

### NullDirectoryStrategy

Decorator which always return null directory.

### PersistentPathStrategy

Use persisted pathname in file. Useful if you want to generate completely random path for each file. (For example UUID
v4) or you just want the path to the file persisted for some reason. Expects instanceof
`Arxy\FilesBundle\Model\PathAwareFile`. It's your responsibility to handle the path itself. You can do that with custom
`Arxy\FilesBundle\ModelFactory` (recommended) or use built in Event Listener, which will set the pathname on
upload (`Arxy\FilesBundle\EventListener\PathAwareListener`)

### UUID V4 Strategy:

Generates random path.

## Migrating between naming strategy.

Register Migrator service and command:

```yaml
services:
    Arxy\FilesBundle\Migrator:
        arguments:
            $filesystem: '@League\Flysystem\FilesystemOperator'
            $oldNamingStrategy: '@old_naming_strategy'
            $newNamingStrategy: '@new_naming_strategy'

    Arxy\FilesBundle\Command\MigrateNamingStrategyCommand:
        arguments:
            $migrator: '@Arxy\FilesBundle\Migrator'
            $repository: '@repository' 
```

then run it.

```shell script
bin/console arxy:files:migrate-naming-strategy
```

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
            $resolvers:
                'App\Entity\File': '@path_resolver'
                'App\Entity\OtherFile': '@other_path_resolver'
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

## Twig Extensions:

1. Arxy\FilesBundle\Twig\FilesExtensions:

- `int 12345|format_bytes(int $precision = 2)` - format bytes as kb,mb, etc.
- `Arxy\FilesBundle\Model\File $file|file_content` - return the contents of file.

2. Arxy\FilesBundle\Twig\PathResolverExtension:

- `file_path(Arxy\FilesBundle\Model\File $file)` - return downloadable path for file using path resolver.

## LiipImagine:

If you need to generate thumbnails for your files, you could use built-in integration
with <a href="https://github.com/liip/LiipImagineBundle">LiipImagineBundle</a>:

1. Setup LiipImagineBundle.
2. Register `Arxy\FilesBundle\LiipImagine\FileFilterPathResolver` as service.
3. Use service from point2 as follows:
   `$pathResolver->getPath(new \Arxy\FilesBundle\LiipImagine\FileFilter($file, 'filterName'));`

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

## Events

# PostUpload

`Arxy\FilesBundle\Events\PostUpload` event is called right after File object is created. It is NOT called if existing
file is found and re-used. At this moment file is located on local FS.

# PreMove

`Arxy\FilesBundle\Events\PreMove` event is called right before File object is moved into its final location. At this
moment file is still located locally. so `ManagerInterface::getPathname()` returns local filepath.

# PostMove

`Arxy\FilesBundle\Events\PostMove` event is called right after File object is moved into its final location. At this
moment file is located in FlySystem. so `ManagerInterface::getPathname()` returns filepath generated from naming
strategy.

# PreUpdate

`Arxy\FilesBundle\Events\PreUpdate` event is called right before File object is updated through write, writeStream.

# PostUpdate

`Arxy\FilesBundle\Events\PostUpdate` event is called right after File object is updated through write, writeStream.

# PreRemove

`Arxy\FilesBundle\Events\PreRemove` event is called right before file is deleted from filesystem.

## Preview

There is a sub-system for preview generation for files: It generates preview and saves it as another file. There are 2
ways to enable it:

1. Synchronous generation:
   `Arxy\FilesBundle\Preview\PreviewGeneratorListener`

2. Asynchronous generation using <a href="https://symfony.com/doc/current/messenger.html">Symfony Messenger</a>:
   `Arxy\FilesBundle\Preview\GeneratePreviewMessageHandler`
   `Arxy\FilesBundle\Preview\PreviewGeneratorMessengerListener`

And then register common services:

```yaml
Imagine\Gd\Imagine: ~

Arxy\FilesBundle\Preview\ImagePreviewGenerator:
    $manager: '@public' <-- manager of files.
    $imagine: '@Imagine\Gd\Imagine'

Arxy\FilesBundle\Preview\Dimension:
    $width: 250 <- width of generated thumbnail
    $height: 250 <- height of generated thumbnail

Arxy\FilesBundle\Preview\DimensionInterface: '@Arxy\FilesBundle\Preview\Dimension'

Arxy\FilesBundle\Preview\PreviewGenerator:
    $manager: '@preview' <- manager of previews
    $generators:
        - '@Arxy\FilesBundle\Preview\ImagePreviewGenerator'
```

Currently, only image preview generator exists. You can add your own image preview generator. Just implement the
`Arxy\FilesBundle\Preview\PreviewGeneratorInterface`.

## Known issues

- If file entity is deleted within transaction and transaction is rolled back - file will be deleted.
- Currently, files are deleted on `preRemove` event, since if `postRemove` is used in combination
  with `IdToPathStrategy` - that results in bug, because Doctrine nulls the `id` after deletion.