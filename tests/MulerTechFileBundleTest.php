<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Tests;

use MulerTech\FileBundle\Entity\ConcreteFile;
use MulerTech\FileBundle\MulerTechFileBundle;
use MulerTech\FileBundle\Service\FileManager;
use MulerTech\FileBundle\Service\FileManagerInterface;
use MulerTech\FileBundle\Storage\DirectoryStrategyInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class MulerTechFileBundleTest extends TestCase
{
    public function testBundleExtendsAbstractBundle(): void
    {
        $bundle = new MulerTechFileBundle();

        self::assertInstanceOf(AbstractBundle::class, $bundle);
    }

    public function testBundleHasCorrectAlias(): void
    {
        $bundle = new MulerTechFileBundle();

        self::assertSame('mulertech_file', $bundle->getContainerExtension()->getAlias());
    }

    public function testLoadExtensionWithDefaultDirectoryStrategy(): void
    {
        $containerBuilder = $this->loadBundleConfig([
            'storage_directory' => '/tmp/files',
            'file_class' => ConcreteFile::class,
        ]);

        self::assertTrue($containerBuilder->has('mulertech_file.file_manager'));
        self::assertTrue($containerBuilder->has('mulertech_file.directory_strategy'));
        self::assertTrue($containerBuilder->hasAlias(FileManagerInterface::class));
        self::assertTrue($containerBuilder->hasAlias(FileManager::class));
        self::assertTrue($containerBuilder->hasAlias(DirectoryStrategyInterface::class));

        $alias = $containerBuilder->getAlias(DirectoryStrategyInterface::class);
        self::assertSame('mulertech_file.directory_strategy', (string) $alias);
    }

    public function testLoadExtensionWithCustomDirectoryStrategy(): void
    {
        $containerBuilder = $this->loadBundleConfig([
            'storage_directory' => '/tmp/files',
            'file_class' => ConcreteFile::class,
            'directory_strategy' => 'app.custom_directory_strategy',
        ]);

        self::assertTrue($containerBuilder->has('mulertech_file.file_manager'));
        self::assertFalse($containerBuilder->has('mulertech_file.directory_strategy'));

        $alias = $containerBuilder->getAlias(DirectoryStrategyInterface::class);
        self::assertSame('app.custom_directory_strategy', (string) $alias);
    }

    public function testLoadExtensionPassesConfigToFileManager(): void
    {
        $containerBuilder = $this->loadBundleConfig([
            'storage_directory' => '/tmp/custom',
            'file_class' => ConcreteFile::class,
            'max_file_size' => 10485760,
            'allowed_mime_types' => ['application/pdf'],
        ]);

        $definition = $containerBuilder->getDefinition('mulertech_file.file_manager');
        $args = $definition->getArguments();

        self::assertSame('/tmp/custom', $args['$storageDirectory']);
        self::assertSame(ConcreteFile::class, $args['$fileClass']);
        self::assertSame(10485760, $args['$maxFileSize']);
        self::assertSame(['application/pdf'], $args['$allowedMimeTypes']);
    }

    public function testLoadExtensionRegistersTwigExtension(): void
    {
        $containerBuilder = $this->loadBundleConfig([
            'storage_directory' => '/tmp/files',
            'file_class' => ConcreteFile::class,
        ]);

        self::assertTrue($containerBuilder->has('mulertech_file.twig_extension'));

        $definition = $containerBuilder->getDefinition('mulertech_file.twig_extension');
        self::assertTrue($definition->hasTag('twig.extension'));
    }

    public function testConfigureUsesDefaultValues(): void
    {
        $containerBuilder = $this->loadBundleConfig([
            'storage_directory' => '/tmp/files',
            'file_class' => ConcreteFile::class,
        ]);

        $definition = $containerBuilder->getDefinition('mulertech_file.file_manager');
        $args = $definition->getArguments();

        self::assertSame(52428800, $args['$maxFileSize']);
        self::assertIsArray($args['$allowedMimeTypes']);
        self::assertContains('application/pdf', $args['$allowedMimeTypes']);
        self::assertContains('image/jpeg', $args['$allowedMimeTypes']);
    }

    public function testConfigureRequiresStorageDirectory(): void
    {
        $this->expectException(\Exception::class);

        $this->loadBundleConfig([
            'file_class' => ConcreteFile::class,
        ]);
    }

    public function testConfigureRequiresFileClass(): void
    {
        $this->expectException(\Exception::class);

        $this->loadBundleConfig([
            'storage_directory' => '/tmp/files',
        ]);
    }

    public function testConfigureRejectsEmptyStorageDirectory(): void
    {
        $this->expectException(\Exception::class);

        $this->loadBundleConfig([
            'storage_directory' => '',
            'file_class' => ConcreteFile::class,
        ]);
    }

    public function testConfigureRejectsInvalidMaxFileSize(): void
    {
        $this->expectException(\Exception::class);

        $this->loadBundleConfig([
            'storage_directory' => '/tmp/files',
            'file_class' => ConcreteFile::class,
            'max_file_size' => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadBundleConfig(array $config): ContainerBuilder
    {
        $bundle = new MulerTechFileBundle();
        $extension = $bundle->getContainerExtension();
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->setParameter('kernel.environment', 'test');
        $containerBuilder->setParameter('kernel.build_dir', sys_get_temp_dir());
        $containerBuilder->setParameter('kernel.debug', true);

        $extension->load([$config], $containerBuilder);

        return $containerBuilder;
    }
}
