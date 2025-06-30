<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Vuryss\DoctrineJsonOdm\Serializer\SymfonySerializerAdapter;
use Vuryss\DoctrineJsonOdm\Tests\Fixtures\TestEntity;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapper;

class SymfonySerializerAdapterTest extends TestCase
{
    private SymfonySerializerAdapter $adapter;

    protected function setUp(): void
    {
        $symfonySerializer = new SymfonySerializer(
            [new ArrayDenormalizer(), new ObjectNormalizer()],
            [new JsonEncoder()]
        );

        $typeMapper = new TypeMapper([
            'test_entity' => TestEntity::class,
        ]);

        $this->adapter = new SymfonySerializerAdapter($symfonySerializer, $typeMapper);
    }

    public function testSerializeAddsTypeInformation(): void
    {
        $entity = new TestEntity('John', 30, true, 'john@example.com');

        $json = $this->adapter->serialize($entity);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('#type', $data);
        $this->assertEquals('test_entity', $data['#type']);
        $this->assertEquals('John', $data['name']);
        $this->assertEquals(30, $data['age']);
        $this->assertTrue($data['active']);
        $this->assertEquals('john@example.com', $data['email']);
    }

    public function testDeserializeUsesTypeInformation(): void
    {
        $json = '{"#type":"test_entity","name":"Jane","age":25,"active":false,"email":"jane@example.com"}';

        $entity = $this->adapter->deserialize($json);

        $this->assertInstanceOf(TestEntity::class, $entity);
        $this->assertEquals('Jane', $entity->getName());
        $this->assertEquals(25, $entity->getAge());
        $this->assertFalse($entity->isActive());
        $this->assertEquals('jane@example.com', $entity->getEmail());
    }

    public function testNormalizeAddsTypeInformation(): void
    {
        $entity = new TestEntity('Bob', 40);

        $data = $this->adapter->normalize($entity);

        $this->assertArrayHasKey('#type', $data);
        $this->assertEquals('test_entity', $data['#type']);
        $this->assertEquals('Bob', $data['name']);
        $this->assertEquals(40, $data['age']);
    }

    public function testDenormalizeUsesTypeInformation(): void
    {
        $data = [
            '#type' => 'test_entity',
            'name' => 'Alice',
            'age' => 35,
            'active' => true,
            'email' => 'alice@example.com',
        ];

        $entity = $this->adapter->denormalize($data, '');

        $this->assertInstanceOf(TestEntity::class, $entity);
        $this->assertEquals('Alice', $entity->getName());
        $this->assertEquals(35, $entity->getAge());
        $this->assertTrue($entity->isActive());
        $this->assertEquals('alice@example.com', $entity->getEmail());
    }

    public function testSerializeDeserializeRoundtrip(): void
    {
        $original = new TestEntity('Test User', 42, false, 'test@example.com');

        $json = $this->adapter->serialize($original);
        $deserialized = $this->adapter->deserialize($json);

        $this->assertInstanceOf(TestEntity::class, $deserialized);
        $this->assertEquals($original->getName(), $deserialized->getName());
        $this->assertEquals($original->getAge(), $deserialized->getAge());
        $this->assertEquals($original->isActive(), $deserialized->isActive());
        $this->assertEquals($original->getEmail(), $deserialized->getEmail());
    }
}
