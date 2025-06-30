<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use Vuryss\DoctrineJsonOdm\Exception\SerializationException;
use Vuryss\DoctrineJsonOdm\Lazy\LazyCollection;
use Vuryss\DoctrineJsonOdm\Lazy\LazyObject;
use Vuryss\DoctrineJsonOdm\Serializer\SerializerInterface;

/**
 * Custom Doctrine type for JSON documents with lazy loading support.
 */
class JsonDocumentType extends JsonType
{
    public const NAME = 'json_document';

    private static ?SerializerInterface $serializer = null;

    /** @var array<string, mixed> */
    private static array $serializationContext = [];

    /** @var array<string, mixed> */
    private static array $deserializationContext = [];

    private static bool $useLazyLoading = true;
    private static string $lazyStrategy = 'ghost'; // 'ghost' or 'proxy'

    /**
     * Set the serializer to use for this type.
     */
    public static function setSerializer(SerializerInterface $serializer): void
    {
        self::$serializer = $serializer;
    }

    /**
     * Get the configured serializer.
     */
    public static function getSerializer(): SerializerInterface
    {
        if (null === self::$serializer) {
            throw SerializationException::serializationFailed('No serializer configured for JsonDocumentType');
        }

        return self::$serializer;
    }

    /**
     * Set serialization context.
     *
     * @param array<string, mixed> $context
     */
    public static function setSerializationContext(array $context): void
    {
        self::$serializationContext = $context;
    }

    /**
     * Set deserialization context.
     *
     * @param array<string, mixed> $context
     */
    public static function setDeserializationContext(array $context): void
    {
        self::$deserializationContext = $context;
    }

    /**
     * Enable or disable lazy loading.
     */
    public static function setLazyLoading(bool $enabled): void
    {
        self::$useLazyLoading = $enabled;
    }

    /**
     * Set the lazy loading strategy ('ghost' or 'proxy').
     */
    public static function setLazyStrategy(string $strategy): void
    {
        if (!in_array($strategy, ['ghost', 'proxy'], true)) {
            throw new \InvalidArgumentException("Invalid lazy strategy: {$strategy}. Must be 'ghost' or 'proxy'.");
        }

        self::$lazyStrategy = $strategy;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        try {
            return self::getSerializer()->serialize($value, self::$serializationContext);
        } catch (\Throwable $e) {
            throw SerializationException::serializationFailed($e->getMessage(), $e);
        }
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        // If lazy loading is disabled, deserialize immediately
        if (!self::$useLazyLoading) {
            return $this->deserializeImmediately($value);
        }

        // Parse JSON to determine the type
        try {
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw SerializationException::invalidJsonData($value, $e);
        }

        // Handle arrays (collections)
        if (is_array($data) && !isset($data['#type'])) {
            return $this->createLazyCollection($data, $value);
        }

        // Handle single objects with type information
        if (is_array($data) && isset($data['#type']) && is_string($data['#type'])) {
            return $this->createLazyObject($data['#type'], $value);
        }

        // For scalar values or arrays without type info, return as-is
        return $data;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * Deserialize JSON immediately without lazy loading.
     */
    private function deserializeImmediately(string $json): mixed
    {
        try {
            return self::getSerializer()->deserialize($json, '', self::$deserializationContext);
        } catch (\Throwable $e) {
            throw SerializationException::deserializationFailed($e->getMessage(), $e);
        }
    }

    /**
     * Create a lazy object for the given type and JSON data.
     */
    private function createLazyObject(string $type, string $json): object
    {
        $serializer = self::getSerializer();
        $context = self::$deserializationContext;

        // The serializer adapter will handle type mapping during deserialization,
        // so we pass the full JSON and let it resolve the correct class name
        return match (self::$lazyStrategy) {
            'ghost' => LazyObject::createLazyGhost($type, $json, $serializer, $context),
            'proxy' => LazyObject::createLazyProxy($type, $json, $serializer, $context),
            default => throw new \LogicException('Invalid lazy strategy: '.self::$lazyStrategy),
        };
    }

    /**
     * Create a lazy collection for array data.
     *
     * @param array<int, mixed> $data
     */
    private function createLazyCollection(array $data, string $originalJson): LazyCollection
    {
        // Try to determine item type from first element
        $itemType = 'stdClass';
        if (!empty($data) && is_array($data[0]) && isset($data[0]['#type']) && is_string($data[0]['#type'])) {
            $itemType = $data[0]['#type'];
        }

        /** @var array<int, array<string, mixed>> $typedData */
        $typedData = $data;

        return LazyCollection::fromArray(
            $typedData,
            $itemType,
            self::getSerializer(),
            self::$deserializationContext
        );
    }
}
