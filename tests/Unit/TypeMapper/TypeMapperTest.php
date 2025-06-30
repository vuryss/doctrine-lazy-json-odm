<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Tests\Unit\TypeMapper;

use PHPUnit\Framework\TestCase;
use Vuryss\DoctrineJsonOdm\TypeMapper\TypeMapper;

class TypeMapperTest extends TestCase
{
    public function testGetTypeByClassReturnsAliasWhenConfigured(): void
    {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'product' => 'App\\Entity\\Product',
        ];

        $mapper = new TypeMapper($typeMap);

        $this->assertEquals('user', $mapper->getTypeByClass('App\\Entity\\User'));
        $this->assertEquals('product', $mapper->getTypeByClass('App\\Entity\\Product'));
    }

    public function testGetTypeByClassReturnsClassNameWhenNotConfigured(): void
    {
        $mapper = new TypeMapper([]);

        $this->assertEquals('App\\Entity\\Unknown', $mapper->getTypeByClass('App\\Entity\\Unknown'));
    }

    public function testGetClassByTypeReturnsMappedClass(): void
    {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'product' => 'App\\Entity\\Product',
        ];

        $mapper = new TypeMapper($typeMap);

        $this->assertEquals('App\\Entity\\User', $mapper->getClassByType('user'));
        $this->assertEquals('App\\Entity\\Product', $mapper->getClassByType('product'));
    }

    public function testGetClassByTypeReturnsTypeWhenNotMapped(): void
    {
        $mapper = new TypeMapper([]);

        $this->assertEquals('unknown', $mapper->getClassByType('unknown'));
    }

    public function testHasTypeForClass(): void
    {
        $typeMap = [
            'user' => 'App\\Entity\\User',
        ];

        $mapper = new TypeMapper($typeMap);

        $this->assertTrue($mapper->hasTypeForClass('App\\Entity\\User'));
        $this->assertFalse($mapper->hasTypeForClass('App\\Entity\\Unknown'));
    }

    public function testHasClassForType(): void
    {
        $typeMap = [
            'user' => 'App\\Entity\\User',
        ];

        $mapper = new TypeMapper($typeMap);

        $this->assertTrue($mapper->hasClassForType('user'));
        $this->assertFalse($mapper->hasClassForType('unknown'));
    }

    public function testGetTypeMap(): void
    {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'product' => 'App\\Entity\\Product',
        ];

        $mapper = new TypeMapper($typeMap);

        $this->assertEquals($typeMap, $mapper->getTypeMap());
    }
}
