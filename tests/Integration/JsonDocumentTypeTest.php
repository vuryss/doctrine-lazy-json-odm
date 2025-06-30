<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Tests\Integration;

use Doctrine\DBAL\Platforms\MySQL80Platform;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Vuryss\DoctrineJsonOdm\Lazy\LazyCollection;
use Vuryss\DoctrineJsonOdm\Lazy\LazyObject;
use Vuryss\DoctrineJsonOdm\Serializer\SymfonySerializerAdapter;
use Vuryss\DoctrineJsonOdm\Tests\Fixtures\TestEntity;
use Vuryss\DoctrineJsonOdm\Type\JsonDocumentType;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapper;

class JsonDocumentTypeTest extends TestCase
{
    private JsonDocumentType $type;
    private MySQL80Platform $platform;

    protected function setUp(): void
    {
        $this->type = new JsonDocumentType();
        $this->platform = new MySQL80Platform();

        // Set up serializer
        $symfonySerializer = new SymfonySerializer(
            [new ArrayDenormalizer(), new ObjectNormalizer()],
            [new JsonEncoder()]
        );

        $typeMapper = new TypeMapper([
            'test_entity' => TestEntity::class,
        ]);

        $serializer = new SymfonySerializerAdapter($symfonySerializer, $typeMapper);
        JsonDocumentType::setSerializer($serializer);
        JsonDocumentType::setLazyLoading(true);
        JsonDocumentType::setLazyStrategy('ghost');
    }

    public function testConvertToDatabaseValueSerializesObject(): void
    {
        $entity = new TestEntity('John Doe', 30, true, 'john@example.com');

        $databaseValue = $this->type->convertToDatabaseValue($entity, $this->platform);

        $this->assertIsString($databaseValue);
        $data = json_decode($databaseValue, true);
        $this->assertArrayHasKey('#type', $data);
        $this->assertEquals('test_entity', $data['#type']);
        $this->assertEquals('John Doe', $data['name']);
        $this->assertEquals(30, $data['age']);
        $this->assertTrue($data['active']);
        $this->assertEquals('john@example.com', $data['email']);
    }

    public function testConvertToPhpValueCreatesLazyObject(): void
    {
        $json = '{"#type":"test_entity","name":"Jane Doe","age":25,"active":false,"email":"jane@example.com"}';

        $phpValue = $this->type->convertToPHPValue($json, $this->platform);

        $this->assertInstanceOf(TestEntity::class, $phpValue);
        $this->assertTrue(LazyObject::isLazy($phpValue));

        // Accessing property should initialize the object
        $name = $phpValue->name;
        $this->assertEquals('Jane Doe', $name);
        $this->assertFalse(LazyObject::isLazy($phpValue));

        // All properties should be accessible
        $this->assertEquals(25, $phpValue->age);
        $this->assertFalse($phpValue->active);
        $this->assertEquals('jane@example.com', $phpValue->email);
    }

    public function testConvertToPhpValueCreatesLazyCollection(): void
    {
        $json = '[
            {"#type":"test_entity","name":"User1","age":20,"active":true,"email":"user1@example.com"},
            {"#type":"test_entity","name":"User2","age":30,"active":false,"email":"user2@example.com"},
            {"#type":"test_entity","name":"User3","age":40,"active":true,"email":"user3@example.com"}
        ]';

        $phpValue = $this->type->convertToPHPValue($json, $this->platform);

        $this->assertInstanceOf(LazyCollection::class, $phpValue);
        $this->assertCount(3, $phpValue);

        // Access first item
        $firstItem = $phpValue[0];
        $this->assertInstanceOf(TestEntity::class, $firstItem);
        $this->assertEquals('User1', $firstItem->getName());
        $this->assertEquals(20, $firstItem->getAge());

        // Iterate through collection
        $names = [];
        foreach ($phpValue as $item) {
            $names[] = $item->getName();
        }
        $this->assertEquals(['User1', 'User2', 'User3'], $names);
    }

    public function testRoundtripSerializationWithLazyLoading(): void
    {
        $originalEntity = new TestEntity('Test User', 42, false, 'test@example.com');

        // Convert to database value
        $databaseValue = $this->type->convertToDatabaseValue($originalEntity, $this->platform);

        // Convert back to PHP value (should be lazy)
        $lazyEntity = $this->type->convertToPHPValue($databaseValue, $this->platform);

        $this->assertInstanceOf(TestEntity::class, $lazyEntity);
        $this->assertTrue(LazyObject::isLazy($lazyEntity));

        // Access properties to trigger initialization
        $this->assertEquals('Test User', $lazyEntity->getName());
        $this->assertEquals(42, $lazyEntity->getAge());
        $this->assertFalse($lazyEntity->isActive());
        $this->assertEquals('test@example.com', $lazyEntity->getEmail());

        $this->assertFalse(LazyObject::isLazy($lazyEntity));
    }

    public function testLazyLoadingDisabled(): void
    {
        JsonDocumentType::setLazyLoading(false);

        $json = '{"#type":"test_entity","name":"Immediate","age":35,"active":true,"email":"immediate@example.com"}';

        $phpValue = $this->type->convertToPHPValue($json, $this->platform);

        $this->assertInstanceOf(TestEntity::class, $phpValue);
        $this->assertFalse(LazyObject::isLazy($phpValue));
        $this->assertEquals('Immediate', $phpValue->getName());

        // Reset for other tests
        JsonDocumentType::setLazyLoading(true);
    }

    public function testLazyProxyStrategy(): void
    {
        JsonDocumentType::setLazyStrategy('proxy');

        $json = '{"#type":"test_entity","name":"Proxy User","age":28,"active":false,"email":"proxy@example.com"}';

        $phpValue = $this->type->convertToPHPValue($json, $this->platform);

        $this->assertInstanceOf(TestEntity::class, $phpValue);
        $this->assertTrue(LazyObject::isLazy($phpValue));

        // Access property to trigger initialization
        $name = $phpValue->getName();
        $this->assertEquals('Proxy User', $name);
        $this->assertFalse(LazyObject::isLazy($phpValue));

        // Reset for other tests
        JsonDocumentType::setLazyStrategy('ghost');
    }

    public function testNullValues(): void
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
        $this->assertNull($this->type->convertToPHPValue('', $this->platform));
    }

    public function testScalarValues(): void
    {
        $scalarJson = '"simple string"';
        $phpValue = $this->type->convertToPHPValue($scalarJson, $this->platform);
        $this->assertEquals('simple string', $phpValue);

        $numberJson = '42';
        $phpValue = $this->type->convertToPHPValue($numberJson, $this->platform);
        $this->assertEquals(42, $phpValue);
    }

    public function testTypeName(): void
    {
        $this->assertEquals('json_document', $this->type->getName());
    }

    public function testRequiresSqlCommentHint(): void
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }
}
