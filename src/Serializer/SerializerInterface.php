<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Serializer;

/**
 * Unified interface for different serializer implementations.
 */
interface SerializerInterface
{
    /**
     * Serialize data to JSON string.
     *
     * @param mixed                $data    The data to serialize
     * @param array<string, mixed> $context Serialization context
     *
     * @return string JSON representation
     */
    public function serialize(mixed $data, array $context = []): string;

    /**
     * Deserialize JSON string to PHP data.
     *
     * @param string               $json    JSON string to deserialize
     * @param string               $type    Target type (class name or empty for auto-detection)
     * @param array<string, mixed> $context Deserialization context
     *
     * @return mixed Deserialized data
     */
    public function deserialize(string $json, string $type = '', array $context = []): mixed;

    /**
     * Normalize data to array representation.
     *
     * @param mixed                $data    The data to normalize
     * @param array<string, mixed> $context Normalization context
     *
     * @return array<string, mixed> Normalized data
     */
    public function normalize(mixed $data, array $context = []): array;

    /**
     * Denormalize array data to object.
     *
     * @param array<string, mixed> $data    The data to denormalize
     * @param string               $type    Target type (class name)
     * @param array<string, mixed> $context Denormalization context
     *
     * @return mixed Denormalized object
     */
    public function denormalize(array $data, string $type, array $context = []): mixed;
}
