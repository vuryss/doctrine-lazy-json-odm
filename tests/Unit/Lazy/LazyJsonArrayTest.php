<?php

declare(strict_types=1);

use Vuryss\DoctrineLazyJsonOdm\Lazy\LazyJsonArray;

describe('LazyJsonArray', function () {
    beforeEach(function () {
        $this->item1 = new stdClass();
        $this->item1->name = 'John Doe';
        $this->item1->email = 'john@example.com';

        $this->item2 = new stdClass();
        $this->item2->name = 'Jane Smith';
        $this->item2->email = 'jane@example.com';

        $this->item3 = new stdClass();
        $this->item3->name = 'Laptop';
        $this->item3->price = '999.99';
    });

    it('can be instantiated with empty array', function () {
        $lazyArray = new LazyJsonArray([]);

        expect($lazyArray)->toBeInstanceOf(LazyJsonArray::class)
            ->and($lazyArray->count())->toBe(0);
    });

    it('can be instantiated with items', function () {
        $items = [$this->item1, $this->item2];
        $lazyArray = new LazyJsonArray($items);

        expect($lazyArray)->toBeInstanceOf(LazyJsonArray::class)
            ->and($lazyArray->count())->toBe(2);
    });

    it('implements ArrayAccess correctly', function () {
        $items = [$this->item1, $this->item2];
        $lazyArray = new LazyJsonArray($items);

        expect(isset($lazyArray[0]))->toBeTrue()
            ->and(isset($lazyArray[1]))->toBeTrue()
            ->and(isset($lazyArray[2]))->toBeFalse()
            ->and($lazyArray[0])->toBe($this->item1)
            ->and($lazyArray[1])->toBe($this->item2);
    });

    it('throws exception when trying to set value', function () {
        $lazyArray = new LazyJsonArray([$this->item1]);

        expect(fn () => $lazyArray[0] = $this->item2)
            ->toThrow(RuntimeException::class, 'The array is read-only. Cannot set value. Please replace the array instead.');
    });

    it('throws exception when trying to unset value', function () {
        $lazyArray = new LazyJsonArray([$this->item1]);

        expect(function () use ($lazyArray) { unset($lazyArray[0]); })
            ->toThrow(RuntimeException::class, 'The array is read-only. Cannot unset value. Please replace the array instead.');
    });

    it('implements Countable correctly', function () {
        $emptyArray = new LazyJsonArray([]);
        $singleArray = new LazyJsonArray([$this->item1]);
        $multiArray = new LazyJsonArray([$this->item1, $this->item2]);

        expect($emptyArray->count())->toBe(0)
            ->and($singleArray->count())->toBe(1)
            ->and($multiArray->count())->toBe(2);
    });

    it('implements IteratorAggregate correctly', function () {
        $items = [$this->item1, $this->item2];
        $lazyArray = new LazyJsonArray($items);

        $iteratedItems = [];
        foreach ($lazyArray as $key => $item) {
            $iteratedItems[$key] = $item;
        }

        expect($iteratedItems)->toBe($items);
    });

    it('can append items', function () {
        $lazyArray = new LazyJsonArray([$this->item1]);
        $newArray = $lazyArray->append($this->item2);

        expect($lazyArray->count())->toBe(1)
            ->and($newArray->count())->toBe(2)
            ->and($newArray[0])->toBe($this->item1)
            ->and($newArray[1])->toBe($this->item2); // Original unchanged
    });

    it('can sort items', function () {
        $items = [$this->item2, $this->item1]; // Jane, John
        $lazyArray = new LazyJsonArray($items);

        $sortedArray = $lazyArray->sort(fn ($a, $b) => strcmp($a->name, $b->name));

        expect($lazyArray->count())->toBe(2)
            ->and($sortedArray->count())->toBe(2)
            ->and($sortedArray[0]->name)->toBe('Jane Smith')
            ->and($sortedArray[1]->name)->toBe('John Doe'); // Original unchanged
    });

    it('returns items array', function () {
        $items = [$this->item1, $this->item2];
        $lazyArray = new LazyJsonArray($items);

        expect($lazyArray->getItems())->toBe($items);
    });

    it('handles mixed object types', function () {
        $items = [$this->item1, $this->item3];
        $lazyArray = new LazyJsonArray($items);

        expect($lazyArray->count())->toBe(2)
            ->and($lazyArray[0])->toBe($this->item1)
            ->and($lazyArray[1])->toBe($this->item3);
    });

    it('can chain operations', function () {
        $lazyArray = new LazyJsonArray([$this->item1]);

        $result = $lazyArray
            ->append($this->item2)
            ->append($this->item3)
            ->sort(fn ($a, $b) => strcmp($a->name, $b->name));

        expect($result->count())->toBe(3)
            ->and($result[0]->name)->toBe('Jane Smith')
            ->and($result[1]->name)->toBe('John Doe')
            ->and($result[2]->name)->toBe('Laptop');
    });

    it('preserves object references', function () {
        $lazyArray = new LazyJsonArray([$this->item1]);

        expect($lazyArray[0])->toBe($this->item1)
            ->and($lazyArray[0] === $this->item1)->toBeTrue();
    });
});
