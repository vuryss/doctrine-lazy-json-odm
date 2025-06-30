<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\TypeMapper;

/**
 * Default implementation of TypeMapperInterface.
 * Maps class names to type aliases and vice versa.
 */
class TypeMapper implements TypeMapperInterface
{
    /**
     * @param array<string, string> $typeMap Map of type aliases to class names
     */
    public function __construct(
        private readonly array $typeMap = [],
    ) {
    }

    public function getTypeByClass(string $className): string
    {
        // Search for the class name in the type map values
        foreach ($this->typeMap as $type => $class) {
            if ($class === $className) {
                return $type;
            }
        }

        // Return the class name if no alias is found
        return $className;
    }

    public function getClassByType(string $type): string
    {
        // Return the mapped class name or the type itself if no mapping exists
        return $this->typeMap[$type] ?? $type;
    }

    public function hasTypeForClass(string $className): bool
    {
        return in_array($className, $this->typeMap, true);
    }

    public function hasClassForType(string $type): bool
    {
        return isset($this->typeMap[$type]);
    }

    /**
     * Get all configured type mappings.
     *
     * @return array<string, string>
     */
    public function getTypeMap(): array
    {
        return $this->typeMap;
    }
}
