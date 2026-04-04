<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Tests\Twig;

use MulerTech\FileBundle\Entity\ConcreteFile;
use MulerTech\FileBundle\Service\FileManagerInterface;
use MulerTech\FileBundle\Twig\FileExtension;
use PHPUnit\Framework\TestCase;

final class FileExtensionTest extends TestCase
{
    public function testRegistersTwoFunctions(): void
    {
        $extension = new FileExtension($this->createStub(FileManagerInterface::class));

        $functions = $extension->getFunctions();

        self::assertCount(2, $functions);

        $names = array_map(static fn ($fn) => $fn->getName(), $functions);
        self::assertContains('file_exists_on_disk', $names);
        self::assertContains('format_file_size', $names);
    }

    public function testFileExistsOnDiskDelegatesToFileManager(): void
    {
        $fileManager = $this->createMock(FileManagerInterface::class);
        $extension = new FileExtension($fileManager);

        $file = new ConcreteFile();
        $file->setFilepath('test/file.pdf');

        $fileManager->expects(self::once())
            ->method('fileExistsOnDisk')
            ->with($file)
            ->willReturn(true);

        self::assertTrue($extension->fileExistsOnDisk($file));
    }

    public function testFormatFileSizeDelegatesToFileManager(): void
    {
        $fileManager = $this->createMock(FileManagerInterface::class);
        $extension = new FileExtension($fileManager);

        $fileManager->expects(self::once())
            ->method('formatFileSize')
            ->with('1048576')
            ->willReturn('1 MB');

        self::assertSame('1 MB', $extension->formatFileSize('1048576'));
    }
}
