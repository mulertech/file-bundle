<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Tests\Entity;

use MulerTech\FileBundle\Entity\ConcreteFile;
use MulerTech\FileBundle\Model\FileInterface;
use PHPUnit\Framework\TestCase;

final class AbstractFileTest extends TestCase
{
    public function testImplementsFileInterface(): void
    {
        $file = new ConcreteFile();

        self::assertInstanceOf(FileInterface::class, $file);
    }

    public function testIdIsNullByDefault(): void
    {
        $file = new ConcreteFile();

        self::assertNull($file->getId());
    }

    public function testFilename(): void
    {
        $file = new ConcreteFile();
        $result = $file->setFilename('document.pdf');

        self::assertSame('document.pdf', $file->getFilename());
        self::assertSame($file, $result);
    }

    public function testStoredFilename(): void
    {
        $file = new ConcreteFile();
        $result = $file->setStoredFilename('document-abc123.pdf');

        self::assertSame('document-abc123.pdf', $file->getStoredFilename());
        self::assertSame($file, $result);
    }

    public function testFilepath(): void
    {
        $file = new ConcreteFile();
        $result = $file->setFilepath('uploader_1/document-abc123.pdf');

        self::assertSame('uploader_1/document-abc123.pdf', $file->getFilepath());
        self::assertSame($file, $result);
    }

    public function testMimeType(): void
    {
        $file = new ConcreteFile();
        $result = $file->setMimeType('application/pdf');

        self::assertSame('application/pdf', $file->getMimeType());
        self::assertSame($file, $result);
    }

    public function testSize(): void
    {
        $file = new ConcreteFile();
        $result = $file->setSize('1048576');

        self::assertSame('1048576', $file->getSize());
        self::assertSame($file, $result);
    }

    public function testExtension(): void
    {
        $file = new ConcreteFile();
        $result = $file->setExtension('pdf');

        self::assertSame('pdf', $file->getExtension());
        self::assertSame($file, $result);
    }

    public function testIsPublicDefaultsFalse(): void
    {
        $file = new ConcreteFile();

        self::assertFalse($file->isPublic());
    }

    public function testSetIsPublic(): void
    {
        $file = new ConcreteFile();
        $result = $file->setIsPublic(true);

        self::assertTrue($file->isPublic());
        self::assertSame($file, $result);
    }
}
