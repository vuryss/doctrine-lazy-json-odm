<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\Serializer;

use Psr\Cache\CacheItemPoolInterface;
use Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapperInterface;
use Vuryss\Serializer\Denormalizer\ArrayDenormalizer;
use Vuryss\Serializer\Denormalizer\BasicTypesDenormalizer;
use Vuryss\Serializer\Denormalizer\DateTimeDenormalizer;
use Vuryss\Serializer\Denormalizer\EnumDenormalizer;
use Vuryss\Serializer\Denormalizer\InterfaceDenormalizer;
use Vuryss\Serializer\Denormalizer\ObjectDenormalizer;
use Vuryss\Serializer\ExceptionInterface;
use Vuryss\Serializer\Metadata\CachedMetadataExtractor;
use Vuryss\Serializer\Metadata\MetadataExtractor;
use Vuryss\Serializer\Normalizer\ArrayNormalizer;
use Vuryss\Serializer\Normalizer\BasicTypesNormalizer;
use Vuryss\Serializer\Normalizer\DateTimeNormalizer;
use Vuryss\Serializer\Normalizer\EnumNormalizer;
use Vuryss\Serializer\Normalizer\ObjectNormalizer;
use Vuryss\Serializer\Serializer as VuryssSerializer;

class Serializer implements SerializerInterface
{
    private VuryssSerializer $serializer;

    public function __construct(
        private readonly TypeMapperInterface $typeMapper,
        private readonly ?CacheItemPoolInterface $serializerCache = null,
    ) {
        $this->serializer = new VuryssSerializer(
            normalizers: [
                new BasicTypesNormalizer(),
                new ArrayNormalizer(),
                new EnumNormalizer(),
                new DateTimeNormalizer(),
                new BuiltInObjectTypeNormalizer($this->typeMapper),
                new ObjectNormalizer(),
            ],
            denormalizers: [
                new BasicTypesDenormalizer(),
                new BuiltInObjectTypeDenormalizer($this->typeMapper),
                new ArrayDenormalizer(),
                new EnumDenormalizer(),
                new DateTimeDenormalizer(),
                new ObjectDenormalizer(),
                new InterfaceDenormalizer(),
            ],
            metadataExtractor: new CachedMetadataExtractor(
                new MetadataExtractor(),
                $this->serializerCache
            ),
        );
    }

    public function serialize(mixed $data, array $context = []): string
    {
        try {
            /* @phpstan-ignore argument.type */
            return $this->serializer->serialize($data, 'json', $context);
        } catch (ExceptionInterface $e) {
            throw new SerializerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function deserialize(string $json, array $context = []): mixed
    {
        try {
            /* @phpstan-ignore argument.type */
            return $this->serializer->deserialize($json, '', 'json', $context);
        } catch (ExceptionInterface $e) {
            throw new SerializerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
