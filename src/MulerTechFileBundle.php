<?php

declare(strict_types=1);

namespace MulerTech\FileBundle;

use MulerTech\FileBundle\Service\FileManager;
use MulerTech\FileBundle\Service\FileManagerInterface;
use MulerTech\FileBundle\Storage\DefaultDirectoryStrategy;
use MulerTech\FileBundle\Storage\DirectoryStrategyInterface;
use MulerTech\FileBundle\Twig\FileExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Twig\Extension\AbstractExtension;

final class MulerTechFileBundle extends AbstractBundle
{
    protected string $extensionAlias = 'mulertech_file';

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

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('storage_directory')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('file_class')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('max_file_size')
                    ->defaultValue(52428800)
                    ->min(1)
                ->end()
                ->arrayNode('allowed_mime_types')
                    ->scalarPrototype()->end()
                    ->defaultValue(self::DEFAULT_ALLOWED_MIME_TYPES)
                ->end()
                ->scalarNode('directory_strategy')
                    ->defaultNull()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var string|null $directoryStrategyId */
        $directoryStrategyId = $config['directory_strategy'];

        if (null === $directoryStrategyId) {
            $container->services()
                ->set('mulertech_file.directory_strategy', DefaultDirectoryStrategy::class);

            $directoryStrategyRef = new Reference('mulertech_file.directory_strategy');
        } else {
            $directoryStrategyRef = new Reference($directoryStrategyId);
        }

        $container->services()
            ->set('mulertech_file.file_manager', FileManager::class)
            ->args([
                '$entityManager' => new Reference('doctrine.orm.entity_manager'),
                '$slugger' => new Reference('slugger'),
                '$logger' => new Reference('logger'),
                '$directoryStrategy' => $directoryStrategyRef,
                '$storageDirectory' => $config['storage_directory'],
                '$fileClass' => $config['file_class'],
                '$maxFileSize' => $config['max_file_size'],
                '$allowedMimeTypes' => $config['allowed_mime_types'],
            ]);

        $container->services()
            ->alias(FileManagerInterface::class, 'mulertech_file.file_manager');

        $container->services()
            ->alias(FileManager::class, 'mulertech_file.file_manager');

        $container->services()
            ->alias(DirectoryStrategyInterface::class, null === $directoryStrategyId
                ? 'mulertech_file.directory_strategy'
                : $directoryStrategyId);

        if (class_exists(AbstractExtension::class)) {
            $container->services()
                ->set('mulertech_file.twig_extension', FileExtension::class)
                ->args([
                    '$fileManager' => new Reference('mulertech_file.file_manager'),
                ])
                ->tag('twig.extension');
        }
    }
}
