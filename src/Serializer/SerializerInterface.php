<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\Serializer;

interface SerializerInterface
{
    public const string TYPE_KEY = '#type';

    /**
     * @param array<string, mixed> $context Serialization context
     *
     * @throws SerializerException
     */
    public function serialize(mixed $data, array $context = []): string;

    /**
     * @param array<string, mixed> $context Deserialization context
     *
     * @throws SerializerException
     */
    public function deserialize(string $json, array $context = []): mixed;
}
