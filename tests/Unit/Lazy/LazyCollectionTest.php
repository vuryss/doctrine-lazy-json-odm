<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Tests\Unit\Lazy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Vuryss\DoctrineJsonOdm\Lazy\LazyCollection;
use Vuryss\DoctrineJsonOdm\Serializer\SymfonySerializerAdapter;
use Vuryss\DoctrineJsonOdm\Tests\Fixtures\TestEntity;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapper;

class LazyCollectionTest extends TestCase
{
    private SymfonySerializerAdapter $serializer;

    protected function setUp(): void
    {
        $symfonySerializer = new SymfonySerializer(
            [new ArrayDenormalizer(), new ObjectNormalizer()],
            [new JsonEncoder()]
        );

        $typeMapper = new TypeMapper([
            'test_entity' => TestEntity::class,
        ]);

        $this->serializer = new SymfonySerializerAdapter($symfonySerializer, $typeMapper);
    }

    public function testLazyCollectionFromArray(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'John', 'age' => 30, 'active' => true, 'email' => 'john@example.com'],
            ['#type' => 'test_entity', 'name' => 'Jane', 'age' => 25, 'active' => false, 'email' => 'jane@example.com'],
            ['#type' => 'test_entity', 'name' => 'Bob', 'age' => 40, 'active' => true, 'email' => 'bob@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);

        $this->assertCount(3, $collection);
        $this->assertTrue($collection->offsetExists(0));
        $this->assertTrue($collection->offsetExists(1));
        $this->assertTrue($collection->offsetExists(2));
        $this->assertFalse($collection->offsetExists(3));
    }

    public function testLazyCollectionItemAccess(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'Alice', 'age' => 35, 'active' => true, 'email' => 'alice@example.com'],
            ['#type' => 'test_entity', 'name' => 'Charlie', 'age' => 28, 'active' => false, 'email' => 'charlie@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);

        // Access first item
        $firstItem = $collection[0];
        $this->assertInstanceOf(TestEntity::class, $firstItem);
        $this->assertEquals('Alice', $firstItem->getName());
        $this->assertEquals(35, $firstItem->getAge());

        // Access second item
        $secondItem = $collection[1];
        $this->assertInstanceOf(TestEntity::class, $secondItem);
        $this->assertEquals('Charlie', $secondItem->getName());
        $this->assertEquals(28, $secondItem->getAge());
    }

    public function testLazyCollectionIteration(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'User1', 'age' => 20, 'active' => true, 'email' => 'user1@example.com'],
            ['#type' => 'test_entity', 'name' => 'User2', 'age' => 30, 'active' => false, 'email' => 'user2@example.com'],
            ['#type' => 'test_entity', 'name' => 'User3', 'age' => 40, 'active' => true, 'email' => 'user3@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);

        $names = [];
        foreach ($collection as $index => $item) {
            $this->assertInstanceOf(TestEntity::class, $item);
            $names[] = $item->getName();
        }

        $this->assertEquals(['User1', 'User2', 'User3'], $names);
    }

    public function testLazyCollectionToArray(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'Test1', 'age' => 25, 'active' => true, 'email' => 'test1@example.com'],
            ['#type' => 'test_entity', 'name' => 'Test2', 'age' => 35, 'active' => false, 'email' => 'test2@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);
        $array = $collection->toArray();

        $this->assertCount(2, $array);
        $this->assertInstanceOf(TestEntity::class, $array[0]);
        $this->assertInstanceOf(TestEntity::class, $array[1]);
        $this->assertEquals('Test1', $array[0]->getName());
        $this->assertEquals('Test2', $array[1]->getName());
    }

    public function testLazyCollectionAddItem(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'Original', 'age' => 30, 'active' => true, 'email' => 'original@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);
        $this->assertCount(1, $collection);

        $newItem = new TestEntity('New', 25, false, 'new@example.com');
        $collection->add($newItem);

        $this->assertCount(2, $collection);
        $this->assertEquals('New', $collection[1]->getName());
    }

    public function testLazyCollectionOffsetSet(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'Original', 'age' => 30, 'active' => true, 'email' => 'original@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);

        $newItem = new TestEntity('Replacement', 40, true, 'replacement@example.com');
        $collection[0] = $newItem;

        $this->assertEquals('Replacement', $collection[0]->getName());
        $this->assertEquals(40, $collection[0]->getAge());
    }

    public function testLazyCollectionOffsetUnset(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'First', 'age' => 20, 'active' => true, 'email' => 'first@example.com'],
            ['#type' => 'test_entity', 'name' => 'Second', 'age' => 30, 'active' => false, 'email' => 'second@example.com'],
            ['#type' => 'test_entity', 'name' => 'Third', 'age' => 40, 'active' => true, 'email' => 'third@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);
        $this->assertCount(3, $collection);

        unset($collection[1]); // Remove second item

        $this->assertCount(2, $collection);
        $this->assertEquals('First', $collection[0]->getName());
        $this->assertEquals('Third', $collection[1]->getName()); // Third becomes second
    }

    public function testLazyCollectionContains(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'Searchable', 'age' => 30, 'active' => true, 'email' => 'searchable@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);
        $item = $collection[0];

        $this->assertTrue($collection->contains($item));

        $otherItem = new TestEntity('Other', 25, false, 'other@example.com');
        $this->assertFalse($collection->contains($otherItem));
    }

    public function testLazyCollectionRemove(): void
    {
        $data = [
            ['#type' => 'test_entity', 'name' => 'ToRemove', 'age' => 30, 'active' => true, 'email' => 'remove@example.com'],
            ['#type' => 'test_entity', 'name' => 'ToKeep', 'age' => 25, 'active' => false, 'email' => 'keep@example.com'],
        ];

        $collection = LazyCollection::fromArray($data, TestEntity::class, $this->serializer);
        $itemToRemove = $collection[0];

        $this->assertCount(2, $collection);
        $this->assertTrue($collection->remove($itemToRemove));
        $this->assertCount(1, $collection);
        $this->assertEquals('ToKeep', $collection[0]->getName());

        // Try to remove non-existent item
        $nonExistent = new TestEntity('NonExistent', 50, true, 'nonexistent@example.com');
        $this->assertFalse($collection->remove($nonExistent));
    }
}
