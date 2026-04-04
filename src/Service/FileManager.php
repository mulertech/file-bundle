<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use MulerTech\FileBundle\Model\FileInterface;
use MulerTech\FileBundle\Model\FileUploaderInterface;
use MulerTech\FileBundle\Storage\DirectoryStrategyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class FileManager implements FileManagerInterface
{
    private const array DEFAULT_ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ];

    private const array DEFAULT_ALLOWED_EXTENSIONS = [
        'PDF',
        'DOC',
        'DOCX',
        'XLS',
        'XLSX',
        'TXT',
        'CSV',
        'JPG/JPEG',
        'PNG',
        'GIF',
        'WEBP',
        'SVG',
        'ZIP',
        'RAR',
        '7Z',
    ];

    private const int DEFAULT_MAX_FILE_SIZE = 52428800;

    /**
     * @param class-string<FileInterface> $fileClass
     * @param array<string>               $allowedMimeTypes
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly LoggerInterface $logger,
        private readonly DirectoryStrategyInterface $directoryStrategy,
        private readonly string $storageDirectory,
        private readonly string $fileClass,
        private readonly int $maxFileSize = self::DEFAULT_MAX_FILE_SIZE,
        private readonly array $allowedMimeTypes = self::DEFAULT_ALLOWED_MIME_TYPES,
    ) {
    }

    public function upload(
        UploadedFile $file,
        FileUploaderInterface $uploader,
        ?object $context = null,
        bool $isPublic = false,
    ): FileInterface {
        $this->validateFile($file);

        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?? 'bin');

        $storedFilename = $safeFilename.'-'.uniqid('', true).'.'.$extension;

        $relativeDirectory = $this->directoryStrategy->getRelativeDirectory($uploader, $context);
        $absoluteDirectory = $this->storageDirectory.'/'.$relativeDirectory;

        $this->ensureDirectoryExists($absoluteDirectory);

        $destinationPath = $absoluteDirectory.'/'.$storedFilename;

        $this->writeFile($file, $destinationPath, $originalFilename);

        $fileEntity = $this->createFileEntity();
        $fileEntity->setFilename($file->getClientOriginalName());
        $fileEntity->setStoredFilename($storedFilename);
        $fileEntity->setFilepath($relativeDirectory.'/'.$storedFilename);
        $fileEntity->setMimeType($file->getMimeType() ?? 'application/octet-stream');
        $fileEntity->setSize((string) $file->getSize());
        $fileEntity->setExtension($extension);
        $fileEntity->setIsPublic($isPublic);

        $this->entityManager->persist($fileEntity);
        $this->entityManager->flush();

        $this->logger->info('File uploaded successfully', [
            'file_id' => $fileEntity->getId(),
            'filename' => $fileEntity->getFilename(),
            'uploader_id' => $uploader->getId(),
        ]);

        return $fileEntity;
    }

    public function delete(FileInterface $file): void
    {
        $absolutePath = $this->storageDirectory.'/'.$file->getFilepath();

        if (file_exists($absolutePath) && !unlink($absolutePath)) {
            $this->logger->warning('Failed to delete file from filesystem', [
                'file_id' => $file->getId(),
                'filepath' => $absolutePath,
            ]);
        }

        $this->entityManager->remove($file);
        $this->entityManager->flush();

        $this->logger->info('File deleted successfully', [
            'file_id' => $file->getId(),
            'filename' => $file->getFilename(),
        ]);
    }

    public function getAbsolutePath(FileInterface $file): string
    {
        return $this->storageDirectory.'/'.$file->getFilepath();
    }

    public function fileExistsOnDisk(FileInterface $file): bool
    {
        return file_exists($this->getAbsolutePath($file));
    }

    public function formatFileSize(string $bytes): string
    {
        $size = (int) $bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size >= 1024 && $i < \count($units) - 1; ++$i) {
            $size /= 1024;
        }

        return round($size, 2).' '.$units[$i];
    }

    public function getMaxFileSizeInMB(): int
    {
        return (int) ($this->maxFileSize / 1048576);
    }

    /**
     * @return array<string>
     */
    public function getAllowedExtensions(): array
    {
        return self::DEFAULT_ALLOWED_EXTENSIONS;
    }

    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > $this->maxFileSize) {
            throw new FileException(sprintf('File size (%s) exceeds maximum allowed size (%s)', $this->formatFileSize((string) $file->getSize()), $this->formatFileSize((string) $this->maxFileSize)));
        }

        $mimeType = $file->getMimeType();
        if (!\in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new FileException(sprintf('File type "%s" is not allowed. Allowed types: %s', $mimeType, implode(', ', $this->allowedMimeTypes)));
        }
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new FileException(sprintf('Directory "%s" was not created', $directory));
        }
    }

    private function writeFile(UploadedFile $uploadedFile, string $destinationPath, string $originalFilename): void
    {
        try {
            $fileContent = $uploadedFile->getContent();

            if ('' === $fileContent) {
                $this->logger->error('Failed to read uploaded file content', [
                    'original_filename' => $originalFilename,
                    'temp_path' => $uploadedFile->getPathname(),
                ]);
                throw new FileException('Cannot read uploaded file content.');
            }

            $bytesWritten = file_put_contents($destinationPath, $fileContent);

            if (false === $bytesWritten) {
                $this->logger->error('Failed to write file to destination', [
                    'destination_path' => $destinationPath,
                ]);
                throw new FileException('Cannot write file to destination directory.');
            }

            if (!file_exists($destinationPath)) {
                throw new FileException('File was not saved correctly.');
            }

            $this->logger->info('File written to disk', [
                'destination_path' => $destinationPath,
                'bytes_written' => $bytesWritten,
            ]);
        } catch (FileException $e) {
            $this->logger->error('Failed to upload file', [
                'filename' => $originalFilename,
                'destination_path' => $destinationPath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during file upload', [
                'filename' => $originalFilename,
                'error' => $e->getMessage(),
            ]);
            throw new FileException('Unexpected error during file upload: '.$e->getMessage());
        }
    }

    private function createFileEntity(): FileInterface
    {
        $class = $this->fileClass;

        return new $class();
    }
}
