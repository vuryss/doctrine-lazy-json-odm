<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Serializer;

use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Vuryss\DoctrineJsonOdm\Exception\SerializationException;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapperInterface;

/**
 * Adapter for Symfony Serializer with type mapping support.
 */
class SymfonySerializerAdapter implements SerializerInterface
{
    private const KEY_TYPE = '#type';
    private const KEY_SCALAR = '#scalar';

    public function __construct(
        private readonly SymfonySerializerInterface $serializer,
        private readonly ?TypeMapperInterface $typeMapper = null,
    ) {
    }

    public function serialize(mixed $data, array $context = []): string
    {
        try {
            $normalizedData = $this->normalize($data, $context);

            return json_encode($normalizedData, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\JsonException $e) {
            throw SerializationException::serializationFailed($e->getMessage(), $e);
        } catch (\Throwable $e) {
            throw SerializationException::serializationFailed($e->getMessage(), $e);
        }
    }

    public function deserialize(string $json, string $type = '', array $context = []): mixed
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return $data;
            }

            return $this->denormalize($data, $type, $context);
        } catch (\JsonException $e) {
            throw SerializationException::invalidJsonData($json, $e);
        } catch (\Throwable $e) {
            throw SerializationException::deserializationFailed($e->getMessage(), $e);
        }
    }

    public function normalize(mixed $data, array $context = []): array
    {
        // Symfony serializer has normalize method through NormalizerInterface
        if (!method_exists($this->serializer, 'normalize')) {
            throw new \RuntimeException('Serializer does not support normalization');
        }

        $normalizedData = $this->serializer->normalize($data, 'json', $context);

        if (is_object($data)) {
            $typeName = get_class($data);

            if ($this->typeMapper) {
                $typeName = $this->typeMapper->getTypeByClass($typeName);
            }

            $typeData = [self::KEY_TYPE => $typeName];
            $valueData = is_scalar($normalizedData) ? [self::KEY_SCALAR => $normalizedData] : (array) $normalizedData;
            $normalizedData = array_merge($typeData, $valueData);
        }

        return (array) $normalizedData;
    }

    public function denormalize(array $data, string $type, array $context = []): mixed
    {
        if (isset($data[self::KEY_TYPE]) && is_string($data[self::KEY_TYPE])) {
            $keyType = $data[self::KEY_TYPE];

            if ($this->typeMapper) {
                $keyType = $this->typeMapper->getClassByType($keyType);
            }

            unset($data[self::KEY_TYPE]);

            $data = $data[self::KEY_SCALAR] ?? $data;

            // Symfony serializer has denormalize method through DenormalizerInterface
            if (!method_exists($this->serializer, 'denormalize')) {
                throw new \RuntimeException('Serializer does not support denormalization');
            }

            return $this->serializer->denormalize($data, $keyType, 'json', $context);
        }

        if (empty($type)) {
            return $data;
        }

        if (!method_exists($this->serializer, 'denormalize')) {
            throw new \RuntimeException('Serializer does not support denormalization');
        }

        return $this->serializer->denormalize($data, $type, 'json', $context);
    }
}
