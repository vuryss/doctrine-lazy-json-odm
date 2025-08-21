<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\Lazy;

/**
 * @template T
 *
 * @implements \ArrayAccess<int, T>
 *  @implements \IteratorAggregate<int, T>
 */
readonly class LazyJsonArray implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @param array<int, T> $items
     */
    public function __construct(
        private array $items,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @phpstan-return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \RuntimeException('The array is read-only. Cannot set value. Please replace the array instead.');
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \RuntimeException('The array is read-only. Cannot unset value. Please replace the array instead.');
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @param T $value
     *
     * @return LazyJsonArray<T>
     */
    public function append(mixed $value): LazyJsonArray
    {
        return new self([...$this->items, $value]);
    }

    /**
     * @param T $value
     *
     * @return LazyJsonArray<T>
     */
    public function updateItem(int $index, mixed $value): LazyJsonArray
    {
        $items = $this->items;
        $items[$index] = $value;

        return new self($items);
    }

    /**
     * @return LazyJsonArray<T>
     */
    public function sort(callable $callback): LazyJsonArray
    {
        $items = $this->items;
        usort($items, $callback);

        return new self($items);
    }

    /**
     * @return array<int, T>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
