<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Tests\Unit\Lazy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Vuryss\DoctrineJsonOdm\Lazy\LazyObject;
use Vuryss\DoctrineJsonOdm\Serializer\SymfonySerializerAdapter;
use Vuryss\DoctrineJsonOdm\Tests\Fixtures\TestEntity;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapper;

class LazyObjectTest extends TestCase
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

    public function testCreateLazyGhostDefersDeserialization(): void
    {
        $jsonData = '{"#type":"test_entity","name":"John","age":30,"active":true,"email":"john@example.com"}';

        $lazyObject = LazyObject::createLazyGhost(
            TestEntity::class,
            $jsonData,
            $this->serializer
        );

        // Object should be lazy initially
        $this->assertTrue(LazyObject::isLazy($lazyObject));
        $this->assertInstanceOf(TestEntity::class, $lazyObject);

        // Accessing a property should trigger initialization
        $name = $lazyObject->name;
        $this->assertEquals('John', $name);

        // Object should no longer be lazy after initialization
        $this->assertFalse(LazyObject::isLazy($lazyObject));

        // All properties should be accessible
        $this->assertEquals(30, $lazyObject->age);
        $this->assertTrue($lazyObject->active);
        $this->assertEquals('john@example.com', $lazyObject->email);
    }

    public function testCreateLazyProxyDefersDeserialization(): void
    {
        $jsonData = '{"#type":"test_entity","name":"Jane","age":25,"active":false,"email":"jane@example.com"}';

        $lazyObject = LazyObject::createLazyProxy(
            TestEntity::class,
            $jsonData,
            $this->serializer
        );

        // Object should be lazy initially
        $this->assertTrue(LazyObject::isLazy($lazyObject));
        $this->assertInstanceOf(TestEntity::class, $lazyObject);

        // Accessing a property should trigger initialization
        $name = $lazyObject->name;
        $this->assertEquals('Jane', $name);

        // Object should no longer be lazy after initialization
        $this->assertFalse(LazyObject::isLazy($lazyObject));

        // All properties should be accessible
        $this->assertEquals(25, $lazyObject->age);
        $this->assertFalse($lazyObject->active);
        $this->assertEquals('jane@example.com', $lazyObject->email);
    }

    public function testManualInitialization(): void
    {
        $jsonData = '{"#type":"test_entity","name":"Bob","age":40,"active":true,"email":"bob@example.com"}';

        $lazyObject = LazyObject::createLazyGhost(
            TestEntity::class,
            $jsonData,
            $this->serializer
        );

        $this->assertTrue(LazyObject::isLazy($lazyObject));

        // Manually initialize the object
        LazyObject::initializeLazyObject($lazyObject);

        $this->assertFalse(LazyObject::isLazy($lazyObject));
        $this->assertEquals('Bob', $lazyObject->name);
        $this->assertEquals(40, $lazyObject->age);
    }

    public function testLazyObjectWithMethods(): void
    {
        $jsonData = '{"#type":"test_entity","name":"Alice","age":35,"active":true,"email":"alice@example.com"}';

        $lazyObject = LazyObject::createLazyGhost(
            TestEntity::class,
            $jsonData,
            $this->serializer
        );

        // Method calls should also trigger initialization
        $name = $lazyObject->getName();
        $this->assertEquals('Alice', $name);
        $this->assertFalse(LazyObject::isLazy($lazyObject));

        // Other methods should work normally
        $this->assertEquals(35, $lazyObject->getAge());
        $this->assertTrue($lazyObject->isActive());
        $this->assertEquals('alice@example.com', $lazyObject->getEmail());
    }

    public function testLazyObjectModification(): void
    {
        $jsonData = '{"#type":"test_entity","name":"Charlie","age":28,"active":false,"email":"charlie@example.com"}';

        $lazyObject = LazyObject::createLazyGhost(
            TestEntity::class,
            $jsonData,
            $this->serializer
        );

        // Modifying properties should trigger initialization
        $lazyObject->name = 'Charles';
        $this->assertFalse(LazyObject::isLazy($lazyObject));
        $this->assertEquals('Charles', $lazyObject->name);

        // Original data should still be accessible for other properties
        $this->assertEquals(28, $lazyObject->age);
        $this->assertFalse($lazyObject->active);
        $this->assertEquals('charlie@example.com', $lazyObject->email);
    }

    public function testInvalidClassThrowsException(): void
    {
        $this->expectException(\Vuryss\DoctrineJsonOdm\Exception\LazyLoadingException::class);
        $this->expectExceptionMessage('Class NonExistentClass does not exist');

        LazyObject::createLazyGhost(
            'NonExistentClass',
            '{}',
            $this->serializer
        );
    }

    public function testInvalidJsonThrowsException(): void
    {
        $this->expectException(\Vuryss\DoctrineJsonOdm\Exception\LazyLoadingException::class);

        $lazyObject = LazyObject::createLazyGhost(
            TestEntity::class,
            'invalid json',
            $this->serializer
        );

        // Exception should be thrown when trying to access a property
        $lazyObject->name;
    }
}
