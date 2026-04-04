<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use MulerTech\FileBundle\Entity\ConcreteFile;
use MulerTech\FileBundle\Model\FileUploaderInterface;
use MulerTech\FileBundle\Service\FileManager;
use MulerTech\FileBundle\Storage\DefaultDirectoryStrategy;
use MulerTech\FileBundle\Storage\DirectoryStrategyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

final class FileManagerTest extends TestCase
{
    private SluggerInterface&Stub $slugger;
    private FileUploaderInterface&Stub $uploader;
    private string $storageDirectory;

    protected function setUp(): void
    {
        $this->uploader = $this->createStub(FileUploaderInterface::class);
        $this->uploader->method('getId')->willReturn(1);

        $this->slugger = $this->createStub(SluggerInterface::class);
        $this->slugger->method('slug')->willReturnCallback(
            static fn (string $string): UnicodeString => new UnicodeString(
                preg_replace('/[^a-zA-Z0-9_-]/', '-', $string) ?? $string,
            ),
        );

        $this->storageDirectory = sys_get_temp_dir().'/mulertech_file_test_'.uniqid('', true);
        mkdir($this->storageDirectory, 0700, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storageDirectory);
    }

    public function testUploadCreatesFileEntity(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $fileManager = $this->createFileManager($entityManager);
        $uploadedFile = $this->createTempUploadedFile('test.txt', 'text/plain', 'Hello World');

        $file = $fileManager->upload($uploadedFile, $this->uploader);

        self::assertInstanceOf(ConcreteFile::class, $file);
        self::assertSame('test.txt', $file->getFilename());
        self::assertSame('text/plain', $file->getMimeType());
        self::assertSame('txt', $file->getExtension());
        self::assertFalse($file->isPublic());
    }

    public function testUploadWithPublicFlag(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $fileManager = $this->createFileManager($entityManager);
        $uploadedFile = $this->createTempUploadedFile('image.png', 'image/png', 'PNG content');

        $file = $fileManager->upload($uploadedFile, $this->uploader, isPublic: true);

        self::assertTrue($file->isPublic());
    }

    public function testUploadStoresFileOnDisk(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $fileManager = $this->createFileManager($entityManager);
        $uploadedFile = $this->createTempUploadedFile('test.txt', 'text/plain', 'Hello World');

        $file = $fileManager->upload($uploadedFile, $this->uploader);

        $absolutePath = $fileManager->getAbsolutePath($file);
        self::assertFileExists($absolutePath);
        self::assertSame('Hello World', file_get_contents($absolutePath));
    }

    public function testUploadRejectsOversizedFile(): void
    {
        $fileManager = $this->createFileManager(maxFileSize: 10);

        $uploadedFile = $this->createTempUploadedFile('big.txt', 'text/plain', str_repeat('x', 100));

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');

        $fileManager->upload($uploadedFile, $this->uploader);
    }

    public function testUploadRejectsDisallowedMimeType(): void
    {
        $fileManager = $this->createFileManager(allowedMimeTypes: ['image/jpeg']);

        $uploadedFile = $this->createTempUploadedFile('test.txt', 'text/plain', 'plain text content');

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('is not allowed');

        $fileManager->upload($uploadedFile, $this->uploader);
    }

    public function testDeleteRemovesFileFromDiskAndDatabase(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::exactly(2))->method('flush');
        $entityManager->expects(self::once())->method('remove');

        $fileManager = $this->createFileManager($entityManager);
        $uploadedFile = $this->createTempUploadedFile('test.txt', 'text/plain', 'content');

        $file = $fileManager->upload($uploadedFile, $this->uploader);
        $absolutePath = $fileManager->getAbsolutePath($file);

        self::assertFileExists($absolutePath);

        $fileManager->delete($file);

        self::assertFileDoesNotExist($absolutePath);
    }

    public function testFileExistsOnDisk(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $fileManager = $this->createFileManager($entityManager);
        $uploadedFile = $this->createTempUploadedFile('test.txt', 'text/plain', 'content');

        $file = $fileManager->upload($uploadedFile, $this->uploader);

        self::assertTrue($fileManager->fileExistsOnDisk($file));
    }

    public function testFileExistsOnDiskReturnsFalseForMissingFile(): void
    {
        $fileManager = $this->createFileManager();

        $file = new ConcreteFile();
        $file->setFilepath('nonexistent/file.txt');

        self::assertFalse($fileManager->fileExistsOnDisk($file));
    }

    public function testFormatFileSize(): void
    {
        $fileManager = $this->createFileManager();

        self::assertSame('0 B', $fileManager->formatFileSize('0'));
        self::assertSame('500 B', $fileManager->formatFileSize('500'));
        self::assertSame('1 KB', $fileManager->formatFileSize('1024'));
        self::assertSame('1.5 KB', $fileManager->formatFileSize('1536'));
        self::assertSame('1 MB', $fileManager->formatFileSize('1048576'));
        self::assertSame('1 GB', $fileManager->formatFileSize('1073741824'));
    }

    public function testGetMaxFileSizeInMB(): void
    {
        $fileManager = $this->createFileManager();

        self::assertSame(50, $fileManager->getMaxFileSizeInMB());
    }

    public function testGetMaxFileSizeInMBWithCustomSize(): void
    {
        $fileManager = $this->createFileManager(maxFileSize: 104857600);

        self::assertSame(100, $fileManager->getMaxFileSizeInMB());
    }

    public function testGetAllowedExtensions(): void
    {
        $fileManager = $this->createFileManager();

        $extensions = $fileManager->getAllowedExtensions();

        self::assertContains('PDF', $extensions);
        self::assertContains('DOC', $extensions);
        self::assertContains('PNG', $extensions);
    }

    public function testUploadPassesContextToDirectoryStrategy(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $context = new \stdClass();

        $strategy = $this->createMock(DirectoryStrategyInterface::class);
        $strategy->expects(self::once())
            ->method('getRelativeDirectory')
            ->with($this->uploader, $context)
            ->willReturn('project_42');

        $fileManager = new FileManager(
            entityManager: $entityManager,
            slugger: $this->slugger,
            logger: new NullLogger(),
            directoryStrategy: $strategy,
            storageDirectory: $this->storageDirectory,
            fileClass: ConcreteFile::class,
        );

        $uploadedFile = $this->createTempUploadedFile('test.txt', 'text/plain', 'Hello');
        $file = $fileManager->upload($uploadedFile, $this->uploader, $context);

        self::assertStringStartsWith('project_42/', $file->getFilepath());
    }

    public function testUploadFallsBackToBinExtension(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $uploadedFile = $this->createStub(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn('noext');
        $uploadedFile->method('getClientOriginalExtension')->willReturn('');
        $uploadedFile->method('guessExtension')->willReturn(null);
        $uploadedFile->method('getSize')->willReturn(5);
        $uploadedFile->method('getMimeType')->willReturn('text/plain');
        $uploadedFile->method('getContent')->willReturn('hello');
        $uploadedFile->method('getPathname')->willReturn('/tmp/noext');

        $fileManager = $this->createFileManager($entityManager);
        $file = $fileManager->upload($uploadedFile, $this->uploader);

        self::assertSame('bin', $file->getExtension());
    }

    public function testUploadWithEmptyFileContentThrowsException(): void
    {
        $uploadedFile = $this->createStub(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn('empty.txt');
        $uploadedFile->method('getClientOriginalExtension')->willReturn('txt');
        $uploadedFile->method('getSize')->willReturn(5);
        $uploadedFile->method('getMimeType')->willReturn('text/plain');
        $uploadedFile->method('getContent')->willReturn('');
        $uploadedFile->method('getPathname')->willReturn('/tmp/empty.txt');

        $fileManager = $this->createFileManager();

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Cannot read uploaded file content');

        $fileManager->upload($uploadedFile, $this->uploader);
    }

    public function testUploadWithUnexpectedErrorWrapsException(): void
    {
        $uploadedFile = $this->createStub(UploadedFile::class);
        $uploadedFile->method('getClientOriginalName')->willReturn('test.txt');
        $uploadedFile->method('getClientOriginalExtension')->willReturn('txt');
        $uploadedFile->method('getSize')->willReturn(100);
        $uploadedFile->method('getMimeType')->willReturn('text/plain');
        $uploadedFile->method('getContent')->willThrowException(new \RuntimeException('disk error'));

        $fileManager = $this->createFileManager();

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Unexpected error during file upload');

        $fileManager->upload($uploadedFile, $this->uploader);
    }

    public function testDeleteWhenFileNotOnDisk(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove');
        $entityManager->expects(self::once())->method('flush');

        $fileManager = $this->createFileManager($entityManager);

        $file = new ConcreteFile();
        $file->setFilepath('nonexistent/file.txt');
        $file->setFilename('file.txt');

        $fileManager->delete($file);
    }

    public function testEnsureDirectoryExistsFailureThrowsException(): void
    {
        $blockingFile = $this->storageDirectory.'/uploader_1';
        file_put_contents($blockingFile, 'blocking');

        $fileManager = $this->createFileManager();
        $uploadedFile = $this->createTempUploadedFile('test.txt', 'text/plain', 'content');

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('was not created');

        $fileManager->upload($uploadedFile, $this->uploader);
    }

    /**
     * @param array<string>|null $allowedMimeTypes
     */
    private function createFileManager(
        EntityManagerInterface|MockObject|null $entityManager = null,
        ?int $maxFileSize = null,
        ?array $allowedMimeTypes = null,
    ): FileManager {
        $args = [
            'entityManager' => $entityManager ?? $this->createStub(EntityManagerInterface::class),
            'slugger' => $this->slugger,
            'logger' => new NullLogger(),
            'directoryStrategy' => new DefaultDirectoryStrategy(),
            'storageDirectory' => $this->storageDirectory,
            'fileClass' => ConcreteFile::class,
        ];

        if (null !== $maxFileSize) {
            $args['maxFileSize'] = $maxFileSize;
        }

        if (null !== $allowedMimeTypes) {
            $args['allowedMimeTypes'] = $allowedMimeTypes;
        }

        return new FileManager(...$args);
    }

    private function createTempUploadedFile(string $filename, string $mimeType, string $content): UploadedFile
    {
        $tempPath = sys_get_temp_dir().'/'.$filename;
        file_put_contents($tempPath, $content);

        return new UploadedFile($tempPath, $filename, $mimeType, test: true);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($path);
    }
}
