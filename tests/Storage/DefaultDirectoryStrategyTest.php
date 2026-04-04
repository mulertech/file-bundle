<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Tests\Storage;

use MulerTech\FileBundle\Model\FileUploaderInterface;
use MulerTech\FileBundle\Storage\DefaultDirectoryStrategy;
use PHPUnit\Framework\TestCase;

final class DefaultDirectoryStrategyTest extends TestCase
{
    public function testReturnsUploaderDirectory(): void
    {
        $strategy = new DefaultDirectoryStrategy();
        $uploader = $this->createStub(FileUploaderInterface::class);
        $uploader->method('getId')->willReturn(42);

        self::assertSame('uploader_42', $strategy->getRelativeDirectory($uploader));
    }

    public function testIgnoresContext(): void
    {
        $strategy = new DefaultDirectoryStrategy();
        $uploader = $this->createStub(FileUploaderInterface::class);
        $uploader->method('getId')->willReturn(7);

        $context = new \stdClass();

        self::assertSame('uploader_7', $strategy->getRelativeDirectory($uploader, $context));
    }
}
