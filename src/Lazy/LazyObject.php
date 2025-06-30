<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Lazy;

use Vuryss\DoctrineJsonOdm\Exception\LazyLoadingException;
use Vuryss\DoctrineJsonOdm\Serializer\SerializerInterface;

/**
 * Factory for creating lazy objects using PHP 8.4 lazy object features.
 */
class LazyObject
{
    /**
     * Create a lazy ghost object that defers JSON deserialization until first property access.
     *
     * @param string               $className  The target class name (can be an alias that will be resolved by the serializer)
     * @param string               $jsonData   The JSON data to deserialize
     * @param SerializerInterface  $serializer The serializer to use for deserialization
     * @param array<string, mixed> $context    Deserialization context
     *
     * @return object Lazy ghost object
     */
    public static function createLazyGhost(
        string $className,
        string $jsonData,
        SerializerInterface $serializer,
        array $context = [],
    ): object {
        // Try to resolve the class name from JSON without full deserialization
        $actualClassName = self::resolveClassNameFromJson($className, $jsonData, $serializer);

        if (!class_exists($actualClassName)) {
            throw LazyLoadingException::initializationFailed("Class {$actualClassName} does not exist");
        }

        try {
            $reflector = new \ReflectionClass($actualClassName);

            return $reflector->newLazyGhost(function (object $object) use ($jsonData, $serializer, $context) {
                try {
                    // Deserialize the JSON data
                    $deserializedObject = $serializer->deserialize($jsonData, '', $context);

                    // Copy properties from deserialized object to the lazy ghost
                    self::copyObjectProperties($deserializedObject, $object);
                } catch (\Throwable $e) {
                    throw LazyLoadingException::initializationFailed(
                        'Failed to deserialize JSON data: '.$e->getMessage(),
                        $e
                    );
                }
            });
        } catch (\ReflectionException $e) {
            throw LazyLoadingException::initializationFailed(
                "Failed to create lazy ghost for class {$actualClassName}: ".$e->getMessage(),
                $e
            );
        }
    }

    /**
     * Create a lazy proxy object that defers JSON deserialization until first property access.
     *
     * @param string               $className  The target class name (can be an alias that will be resolved by the serializer)
     * @param string               $jsonData   The JSON data to deserialize
     * @param SerializerInterface  $serializer The serializer to use for deserialization
     * @param array<string, mixed> $context    Deserialization context
     *
     * @return object Lazy proxy object
     */
    public static function createLazyProxy(
        string $className,
        string $jsonData,
        SerializerInterface $serializer,
        array $context = [],
    ): object {
        // Try to resolve the class name from JSON without full deserialization
        $actualClassName = self::resolveClassNameFromJson($className, $jsonData, $serializer);

        if (!class_exists($actualClassName)) {
            throw LazyLoadingException::initializationFailed("Class {$actualClassName} does not exist");
        }

        try {
            $reflector = new \ReflectionClass($actualClassName);

            return $reflector->newLazyProxy(function () use ($jsonData, $serializer, $context) {
                try {
                    // Deserialize and return the real object
                    return $serializer->deserialize($jsonData, '', $context);
                } catch (\Throwable $e) {
                    throw LazyLoadingException::initializationFailed(
                        'Failed to deserialize JSON data: '.$e->getMessage(),
                        $e
                    );
                }
            });
        } catch (\ReflectionException $e) {
            throw LazyLoadingException::initializationFailed(
                "Failed to create lazy proxy for class {$actualClassName}: ".$e->getMessage(),
                $e
            );
        }
    }

    /**
     * Check if an object is a lazy object (ghost or proxy).
     */
    public static function isLazy(object $object): bool
    {
        $reflector = new \ReflectionObject($object);

        return $reflector->isUninitializedLazyObject($object);
    }

    /**
     * Initialize a lazy object if it's not already initialized.
     */
    public static function initializeLazyObject(object $object): void
    {
        $reflector = new \ReflectionObject($object);
        if ($reflector->isUninitializedLazyObject($object)) {
            $reflector->initializeLazyObject($object);
        }
    }

    /**
     * Resolve the actual class name from JSON data without full deserialization.
     */
    private static function resolveClassNameFromJson(
        string $className,
        string $jsonData,
        SerializerInterface $serializer,
    ): string {
        try {
            // Parse JSON to extract type information
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($data) && isset($data['#type'])) {
                $typeAlias = $data['#type'];

                // If the serializer has a type mapper, use it to resolve the class name
                if ($serializer instanceof \Vuryss\DoctrineJsonOdm\Serializer\SymfonySerializerAdapter
                    || $serializer instanceof \Vuryss\DoctrineJsonOdm\Serializer\VuryssSerializerAdapter) {
                    // Use reflection to access the type mapper
                    $reflection = new \ReflectionObject($serializer);
                    $typeMapperProperty = $reflection->getProperty('typeMapper');
                    $typeMapperProperty->setAccessible(true);
                    $typeMapper = $typeMapperProperty->getValue($serializer);

                    if (null !== $typeMapper) {
                        return $typeMapper->getClassByType($typeAlias);
                    }
                }

                // Fallback: return the type alias as class name
                return $typeAlias;
            }
        } catch (\Throwable $e) {
            // If anything fails, fall back to the provided class name
        }

        return $className;
    }

    /**
     * Copy properties from source object to target object using reflection.
     */
    private static function copyObjectProperties(object $source, object $target): void
    {
        $sourceReflector = new \ReflectionObject($source);
        $targetReflector = new \ReflectionObject($target);

        foreach ($sourceReflector->getProperties() as $property) {
            $property->setAccessible(true);

            if ($property->isInitialized($source)) {
                $value = $property->getValue($source);

                // Get corresponding property in target object
                if ($targetReflector->hasProperty($property->getName())) {
                    $targetProperty = $targetReflector->getProperty($property->getName());
                    $targetProperty->setAccessible(true);
                    $targetProperty->setValue($target, $value);
                }
            }
        }
    }
}
