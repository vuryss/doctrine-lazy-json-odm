<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\TypeMapper;

readonly class TypeMapper implements TypeMapperInterface
{
    /** @var array<class-string, string> */
    private array $classMap;

    /**
     * @param array<string, class-string> $typeMap Map of type aliases to class names
     */
    public function __construct(
        private array $typeMap = [],
    ) {
        $this->classMap = array_flip($typeMap);
    }

    public function getTypeByClass(string $className): string
    {
        return $this->classMap[$className] ?? $className;
    }

    public function getClassByType(string $type): string
    {
        // @phpstan-ignore return.type
        return $this->typeMap[$type] ?? $type;
    }
}
