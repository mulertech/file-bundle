# MulerTech File Bundle

___
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mulertech/file-bundle.svg?style=flat-square)](https://packagist.org/packages/mulertech/file-bundle)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mulertech/file-bundle/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mulertech/file-bundle/actions/workflows/tests.yml)
[![GitHub PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/mulertech/file-bundle/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/mulertech/file-bundle/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/mulertech/file-bundle.svg?style=flat-square)](https://packagist.org/packages/mulertech/file-bundle)
[![Test Coverage](https://raw.githubusercontent.com/mulertech/file-bundle/badge/badge-coverage.svg)](https://packagist.org/packages/mulertech/file-bundle)
___

Symfony bundle for file upload management with metadata tracking, configurable validation, and an extensible entity.

## Requirements

- PHP 8.4+
- Symfony 6.4+, 7.0+ or 8.0+
- Doctrine ORM 2.19+ or 3.0+

## Installation

```bash
composer require mulertech/file-bundle
```

## Configuration

```yaml
# config/packages/mulertech_file.yaml
mulertech_file:
    storage_directory: '%kernel.project_dir%/var/documents'
    file_class: App\Entity\File
    # max_file_size: 52428800  # 50MB (default)
    # directory_strategy: null  # Service ID for custom DirectoryStrategyInterface
    # allowed_mime_types:       # Override default MIME types
    #     - 'application/pdf'
    #     - 'image/jpeg'
```

## Usage

### 1. Create your File entity

Extend the provided `AbstractFile` MappedSuperclass:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use MulerTech\FileBundle\Entity\AbstractFile;

#[ORM\Entity]
#[ORM\Table(name: 'files')]
class File extends AbstractFile
{
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $uploadedBy;

    // Add your own relations and fields...
}
```

### 2. Implement FileUploaderInterface

Your User entity must implement `FileUploaderInterface`:

```php
use MulerTech\FileBundle\Model\FileUploaderInterface;

class User implements FileUploaderInterface
{
    public function getId(): ?int { /* ... */ }
}
```

### 3. Upload files

Inject `FileManagerInterface` in your controller or service:

```php
use MulerTech\FileBundle\Service\FileManagerInterface;

class FileController
{
    public function upload(FileManagerInterface $fileManager, Request $request): Response
    {
        $uploadedFile = $request->files->get('file');
        $file = $fileManager->upload($uploadedFile, $user);

        // $file is your concrete entity, persisted and flushed
    }
}
```

### 4. Custom directory strategy (optional)

Implement `DirectoryStrategyInterface` to control where files are stored:

```php
use MulerTech\FileBundle\Storage\DirectoryStrategyInterface;
use MulerTech\FileBundle\Model\FileUploaderInterface;

class ProjectDirectoryStrategy implements DirectoryStrategyInterface
{
    public function getRelativeDirectory(FileUploaderInterface $uploader, ?object $context = null): string
    {
        if ($context instanceof Project) {
            return sprintf('project_%d', $context->getId());
        }

        return sprintf('user_%d', $uploader->getId());
    }
}
```

Then configure:

```yaml
mulertech_file:
    directory_strategy: App\Storage\ProjectDirectoryStrategy
```

### 5. Storage target (for bundles)

A bundle storing files that must be laid out the same way on every site passes as context an object implementing `StorageTargetInterface`. It gives the directory and the start of the stored name itself, and the site's directory strategy is not consulted:

```php
use MulerTech\FileBundle\Storage\StorageTargetInterface;

final readonly class CoverTarget implements StorageTargetInterface
{
    public function __construct(private int $postId)
    {
    }

    public function getRelativeDirectory(): string
    {
        return 'blog/images';
    }

    public function getFilenamePrefix(): string
    {
        return sprintf('image-%d-', $this->postId);
    }
}

$file = $fileManager->upload($uploadedFile, $user, new CoverTarget($post->getId()), true);
// blog/images/image-12-6650c1f2a3b4e1.23456789.webp
```

The unique part and the extension follow the prefix. The directory is made of segments of letters, digits, `_` and `-` joined by `/`, the prefix of the same characters without `/`; anything else (`..`, an absolute path) is refused with an `InvalidArgumentException` naming the target, since it would write outside the storage directory.

The uploader is not stored on the file: it only feeds the directory strategy and the upload log. Deleting a user deletes none of their files, unless the site's own file entity links the two with a cascade.

### 6. Twig functions

```twig
{% if file_exists_on_disk(file) %}
    <span>{{ format_file_size(file.size) }}</span>
{% endif %}
```

## Testing

```bash
./vendor/bin/mtdocker test-ai
```

## License

MIT
