<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\TypeMapper;

/**
 * Interface for mapping between class names and type aliases.
 */
interface TypeMapperInterface
{
    /**
     * @param class-string $className
     */
    public function getTypeByClass(string $className): string;

    /**
     * @param string|class-string $type
     *
     * @return class-string
     */
    public function getClassByType(string $type): string;
}
