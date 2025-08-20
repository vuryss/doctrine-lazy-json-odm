<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Vuryss\DoctrineLazyJsonOdm\Serializer\Serializer;
use Vuryss\DoctrineLazyJsonOdm\Serializer\SerializerInterface;
use Vuryss\DoctrineLazyJsonOdm\Type\LazyJsonDocumentType;
use Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapper;
use Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapperInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Symfony bundle for Doctrine JSON ODM with lazy loading.
 *
 * @phpstan-type Config array{
 *        type_map?: array<string, string>,
 *        cache_pool?: string|null,
 * }
 */
class DoctrineLazyJsonOdmBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        /* @see https://symfony.com/doc/current/components/config/definition.html */
        $definition->rootNode()
            ->children()
                ->arrayNode('type_map')
                    ->info('Map class names to type aliases for storage optimization')
                    ->useAttributeAsKey('alias')
                    ->scalarPrototype()->end()
                    ->example([
                        'user' => 'App\\Entity\\User',
                        'product' => 'App\\Entity\\Product',
                    ])
                ->end()
                ->stringNode('cache_pool')
                    ->info('Cache pool for metadata caching')
                    ->defaultNull()
                ->end()
            ->end();
    }

    /**
     * @throws Exception
     */
    public function boot(): void
    {
        // Register the custom Doctrine type
        if (!Type::hasType(LazyJsonDocumentType::NAME)) {
            Type::addType(LazyJsonDocumentType::NAME, LazyJsonDocumentType::class);

            /** @var LazyJsonDocumentType $type */
            $type = Type::getType(LazyJsonDocumentType::NAME);

            $serializer = $this->container->get('doctrine_lazy_json_odm.serializer');

            if (!$serializer instanceof SerializerInterface) {
                throw new \LogicException('Serializer not configured');
            }

            $typeMapper = $this->container->get(TypeMapperInterface::class);

            if (!$typeMapper instanceof TypeMapperInterface) {
                throw new \LogicException('Type mapper not configured');
            }

            $type->setSerializer($serializer);
            $type->setTypeMapper($typeMapper);
        }
    }

    /**
     * @phpstan-param Config $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $container
            ->services()
            ->get(TypeMapper::class)
            ->arg('$typeMap', $config['type_map'] ?? [])
        ;

        $container
            ->services()
            ->get(Serializer::class)
            ->arg('$serializerCache', isset($config['cache_pool']) ? service($config['cache_pool']) : null)
        ;
    }
}
