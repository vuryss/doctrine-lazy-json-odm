<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\TypeMapper;

/**
 * Interface for mapping between class names and type aliases.
 */
interface TypeMapperInterface
{
    /**
     * Get the type alias for a given class name.
     * Returns the class name if no alias is configured.
     */
    public function getTypeByClass(string $className): string;

    /**
     * Get the class name for a given type alias.
     * Returns the type if no mapping is configured.
     */
    public function getClassByType(string $type): string;

    /**
     * Check if a type alias is configured for the given class.
     */
    public function hasTypeForClass(string $className): bool;

    /**
     * Check if a class mapping is configured for the given type.
     */
    public function hasClassForType(string $type): bool;
}
