<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Lazy;

use Vuryss\DoctrineJsonOdm\Exception\LazyLoadingException;
use Vuryss\DoctrineJsonOdm\Serializer\SerializerInterface;

/**
 * Lazy collection that defers deserialization of individual items until accessed.
 * Implements array-like interfaces for transparent usage.
 */
class LazyCollection implements \Countable, \Iterator, \ArrayAccess
{
    /** @var array<int, mixed> */
    private array $items = [];

    /** @var array<int, string> */
    private array $jsonItems = [];

    /** @var array<int, bool> */
    private array $initialized = [];

    private int $position = 0;

    /**
     * @param array<int, string>   $jsonItems     Array of JSON strings to deserialize
     * @param string               $itemClassName The class name for collection items
     * @param SerializerInterface  $serializer    The serializer to use
     * @param array<string, mixed> $context       Deserialization context
     */
    public function __construct(
        array $jsonItems,
        private readonly string $itemClassName,
        private readonly SerializerInterface $serializer,
        private readonly array $context = [],
    ) {
        $this->jsonItems = array_values($jsonItems); // Reindex to ensure sequential keys
        $this->items = array_fill(0, count($jsonItems), null);
        $this->initialized = array_fill(0, count($jsonItems), false);
    }

    /**
     * Create a lazy collection from an array of data.
     *
     * @param array<int, array<string, mixed>> $data          Array of normalized data
     * @param string                           $itemClassName The class name for collection items
     * @param SerializerInterface              $serializer    The serializer to use
     * @param array<string, mixed>             $context       Deserialization context
     */
    public static function fromArray(
        array $data,
        string $itemClassName,
        SerializerInterface $serializer,
        array $context = [],
    ): self {
        $jsonItems = [];
        foreach ($data as $item) {
            $jsonItems[] = json_encode($item, JSON_THROW_ON_ERROR);
        }

        return new self($jsonItems, $itemClassName, $serializer, $context);
    }

    // Countable interface
    public function count(): int
    {
        return count($this->jsonItems);
    }

    // Iterator interface
    public function current(): mixed
    {
        return $this->offsetGet($this->position);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->offsetExists($this->position);
    }

    // ArrayAccess interface
    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < count($this->jsonItems);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        if (!$this->initialized[$offset]) {
            $this->initializeItem($offset);
        }

        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset) {
            // Append to the end
            $offset = count($this->jsonItems);
            $this->jsonItems[] = $this->serializeItem($value);
            $this->items[] = $value;
            $this->initialized[] = true;
        } elseif (is_int($offset) && $offset >= 0) {
            if ($offset >= count($this->jsonItems)) {
                // Extend arrays if necessary
                $this->extendArrays($offset + 1);
            }

            $this->jsonItems[$offset] = $this->serializeItem($value);
            $this->items[$offset] = $value;
            $this->initialized[$offset] = true;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
            array_splice($this->jsonItems, $offset, 1);
            array_splice($this->items, $offset, 1);
            array_splice($this->initialized, $offset, 1);

            // Adjust position if necessary
            if ($this->position > $offset) {
                --$this->position;
            }
        }
    }

    /**
     * Convert the collection to a regular array.
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        for ($i = 0; $i < count($this->jsonItems); ++$i) {
            $result[] = $this->offsetGet($i);
        }

        return $result;
    }

    /**
     * Add an item to the collection.
     */
    public function add(mixed $item): void
    {
        $this->offsetSet(null, $item);
    }

    /**
     * Remove an item from the collection by value.
     */
    public function remove(mixed $item): bool
    {
        for ($i = 0; $i < count($this->jsonItems); ++$i) {
            if ($this->offsetGet($i) === $item) {
                $this->offsetUnset($i);

                return true;
            }
        }

        return false;
    }

    /**
     * Check if the collection contains an item.
     */
    public function contains(mixed $item): bool
    {
        for ($i = 0; $i < count($this->jsonItems); ++$i) {
            if ($this->offsetGet($i) === $item) {
                return true;
            }
        }

        return false;
    }

    /**
     * Initialize a specific item by deserializing its JSON data.
     */
    private function initializeItem(int $offset): void
    {
        try {
            $this->items[$offset] = $this->serializer->deserialize(
                $this->jsonItems[$offset],
                $this->itemClassName,
                $this->context
            );
            $this->initialized[$offset] = true;
        } catch (\Throwable $e) {
            throw LazyLoadingException::collectionInitializationFailed(
                "Failed to initialize item at offset {$offset}: ".$e->getMessage(),
                $e
            );
        }
    }

    /**
     * Serialize an item to JSON.
     */
    private function serializeItem(mixed $item): string
    {
        try {
            return $this->serializer->serialize($item, $this->context);
        } catch (\Throwable $e) {
            throw LazyLoadingException::collectionInitializationFailed(
                'Failed to serialize item: '.$e->getMessage(),
                $e
            );
        }
    }

    /**
     * Extend internal arrays to the specified size.
     */
    private function extendArrays(int $size): void
    {
        $currentSize = count($this->jsonItems);
        if ($size > $currentSize) {
            $this->jsonItems = array_pad($this->jsonItems, $size, '');
            $this->items = array_pad($this->items, $size, null);
            $this->initialized = array_pad($this->initialized, $size, false);
        }
    }
}
