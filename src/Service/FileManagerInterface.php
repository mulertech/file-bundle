<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Service;

use MulerTech\FileBundle\Exception\FileRejected;
use MulerTech\FileBundle\Model\FileInterface;
use MulerTech\FileBundle\Model\FileUploaderInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileManagerInterface
{
    /**
     * @throws FileRejected  when the file is too large or of a type not allowed
     * @throws FileException when the file could not be received or stored
     */
    public function upload(
        UploadedFile $file,
        FileUploaderInterface $uploader,
        ?object $context = null,
        bool $isPublic = false,
    ): FileInterface;

    public function delete(FileInterface $file): void;

    public function getAbsolutePath(FileInterface $file): string;

    public function fileExistsOnDisk(FileInterface $file): bool;

    public function formatFileSize(string $bytes): string;

    public function getMaxFileSizeInMB(): int;

    /**
     * @return array<string>
     */
    public function getAllowedExtensions(): array;
}
