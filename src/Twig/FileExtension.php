<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Twig;

use MulerTech\FileBundle\Model\FileInterface;
use MulerTech\FileBundle\Service\FileManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FileExtension extends AbstractExtension
{
    public function __construct(
        private readonly FileManagerInterface $fileManager,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_exists_on_disk', $this->fileExistsOnDisk(...)),
            new TwigFunction('format_file_size', $this->formatFileSize(...)),
        ];
    }

    public function fileExistsOnDisk(FileInterface $file): bool
    {
        return $this->fileManager->fileExistsOnDisk($file);
    }

    public function formatFileSize(string $bytes): string
    {
        return $this->fileManager->formatFileSize($bytes);
    }
}
