<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Vuryss\DoctrineJsonOdm\Serializer\SerializerInterface;
use Vuryss\DoctrineJsonOdm\Serializer\SymfonySerializerAdapter;
use Vuryss\DoctrineJsonOdm\Serializer\VuryssSerializerAdapter;
use Vuryss\DoctrineJsonOdm\Type\JsonDocumentType;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapper;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapperInterface;
use Vuryss\Serializer\Metadata\CachedMetadataExtractor;
use Vuryss\Serializer\Metadata\MetadataExtractor;
use Vuryss\Serializer\Serializer as VuryssSerializer;

/**
 * Symfony DI extension for the Doctrine JSON ODM bundle.
 */
class DoctrineJsonOdmExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Register type mapper
        $this->registerTypeMapper($container, $config);

        // Register serializer based on configuration
        $this->registerSerializer($container, $config);

        // Configure the JsonDocumentType
        $this->configureJsonDocumentType($container, $config);
    }

    private function registerTypeMapper(ContainerBuilder $container, array $config): void
    {
        $definition = new Definition(TypeMapper::class, [$config['type_map']]);
        $container->setDefinition(TypeMapperInterface::class, $definition);
        $container->setAlias('doctrine_json_odm.type_mapper', TypeMapperInterface::class);
    }

    private function registerSerializer(ContainerBuilder $container, array $config): void
    {
        $serializerType = $config['serializer'];

        if ('symfony' === $serializerType) {
            $this->registerSymfonySerializer($container, $config);
        } elseif ('vuryss' === $serializerType) {
            $this->registerVuryssSerializer($container, $config);
        }
    }

    private function registerSymfonySerializer(ContainerBuilder $container, array $config): void
    {
        // Register default normalizers if none specified
        $normalizers = $config['symfony_serializer']['normalizers'] ?: [
            BackedEnumNormalizer::class,
            UidNormalizer::class,
            DateTimeNormalizer::class,
            ArrayDenormalizer::class,
            ObjectNormalizer::class,
        ];

        $encoders = $config['symfony_serializer']['encoders'] ?: [
            JsonEncoder::class,
        ];

        // Create normalizer definitions
        $normalizerRefs = [];
        foreach ($normalizers as $normalizer) {
            $normalizerDef = new Definition($normalizer);
            $container->setDefinition('doctrine_json_odm.normalizer.'.md5($normalizer), $normalizerDef);
            $normalizerRefs[] = new Reference('doctrine_json_odm.normalizer.'.md5($normalizer));
        }

        // Create encoder definitions
        $encoderRefs = [];
        foreach ($encoders as $encoder) {
            $encoderDef = new Definition($encoder);
            $container->setDefinition('doctrine_json_odm.encoder.'.md5($encoder), $encoderDef);
            $encoderRefs[] = new Reference('doctrine_json_odm.encoder.'.md5($encoder));
        }

        // Register Symfony serializer
        $symfonySerializerDef = new Definition(SymfonySerializer::class, [$normalizerRefs, $encoderRefs]);
        $container->setDefinition('doctrine_json_odm.symfony_serializer', $symfonySerializerDef);

        // Register adapter
        $adapterDef = new Definition(SymfonySerializerAdapter::class, [
            new Reference('doctrine_json_odm.symfony_serializer'),
            new Reference(TypeMapperInterface::class),
        ]);
        $container->setDefinition(SerializerInterface::class, $adapterDef);
        $container->setAlias('doctrine_json_odm.serializer', SerializerInterface::class);
    }

    private function registerVuryssSerializer(ContainerBuilder $container, array $config): void
    {
        $normalizers = [];
        $denormalizers = [];

        // Add custom normalizers/denormalizers if specified
        foreach ($config['vuryss_serializer']['normalizers'] as $normalizer) {
            $normalizers[] = new Reference($normalizer);
        }

        foreach ($config['vuryss_serializer']['denormalizers'] as $denormalizer) {
            $denormalizers[] = new Reference($denormalizer);
        }

        // Register metadata extractor
        $metadataExtractorDef = new Definition(MetadataExtractor::class);
        $container->setDefinition('doctrine_json_odm.metadata_extractor', $metadataExtractorDef);

        if ($config['vuryss_serializer']['enable_cache']) {
            $cachedExtractorDef = new Definition(CachedMetadataExtractor::class, [
                new Reference('doctrine_json_odm.metadata_extractor'),
            ]);
            $container->setDefinition('doctrine_json_odm.cached_metadata_extractor', $cachedExtractorDef);
            $metadataExtractorRef = new Reference('doctrine_json_odm.cached_metadata_extractor');
        } else {
            $metadataExtractorRef = new Reference('doctrine_json_odm.metadata_extractor');
        }

        // Register Vuryss serializer
        $vuryssSerializerDef = new Definition(VuryssSerializer::class, [
            $normalizers,
            $denormalizers,
            $metadataExtractorRef,
            $config['serialization_context'],
        ]);
        $container->setDefinition('doctrine_json_odm.vuryss_serializer', $vuryssSerializerDef);

        // Register adapter
        $adapterDef = new Definition(VuryssSerializerAdapter::class, [
            new Reference('doctrine_json_odm.vuryss_serializer'),
            new Reference(TypeMapperInterface::class),
        ]);
        $container->setDefinition(SerializerInterface::class, $adapterDef);
        $container->setAlias('doctrine_json_odm.serializer', SerializerInterface::class);
    }

    private function configureJsonDocumentType(ContainerBuilder $container, array $config): void
    {
        // Configure the JsonDocumentType with a compiler pass
        $container->addCompilerPass(new class($config) implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface {
            public function __construct(private array $config)
            {
            }

            public function process(ContainerBuilder $container): void
            {
                if ($container->has(SerializerInterface::class)) {
                    $serializer = $container->get(SerializerInterface::class);
                    JsonDocumentType::setSerializer($serializer);
                    JsonDocumentType::setSerializationContext($this->config['serialization_context']);
                    JsonDocumentType::setDeserializationContext($this->config['deserialization_context']);
                    JsonDocumentType::setLazyLoading($this->config['lazy_loading']);
                    JsonDocumentType::setLazyStrategy($this->config['lazy_strategy']);
                }
            }
        });
    }

    public function getAlias(): string
    {
        return 'doctrine_json_odm';
    }
}
