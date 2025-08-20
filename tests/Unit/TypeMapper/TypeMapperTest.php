<?php

declare(strict_types=1);

use Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapper;

describe('TypeMapper', function () {
    it('can be instantiated without type map', function () {
        $typeMapper = new TypeMapper();

        expect($typeMapper)->toBeInstanceOf(TypeMapper::class);
    });

    it('can be instantiated with type map', function () {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'product' => 'App\\Entity\\Product',
        ];

        $typeMapper = new TypeMapper($typeMap);

        expect($typeMapper)->toBeInstanceOf(TypeMapper::class);
    });

    it('returns class name when no alias is configured', function () {
        $typeMapper = new TypeMapper();

        $result = $typeMapper->getTypeByClass('App\\Entity\\User');

        expect($result)->toBe('App\\Entity\\User');
    });

    it('returns alias when class is mapped', function () {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'product' => 'App\\Entity\\Product',
        ];
        $typeMapper = new TypeMapper($typeMap);

        $result = $typeMapper->getTypeByClass('App\\Entity\\User');

        expect($result)->toBe('user');
    });

    it('returns type when no class is mapped', function () {
        $typeMapper = new TypeMapper();

        $result = $typeMapper->getClassByType('user');

        expect($result)->toBe('user');
    });

    it('returns class name when type is mapped', function () {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'product' => 'App\\Entity\\Product',
        ];
        $typeMapper = new TypeMapper($typeMap);

        $result = $typeMapper->getClassByType('user');

        expect($result)->toBe('App\\Entity\\User');
    });

    it('handles multiple mappings correctly', function () {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'product' => 'App\\Entity\\Product',
            'order' => 'App\\Entity\\Order',
        ];
        $typeMapper = new TypeMapper($typeMap);

        expect($typeMapper->getTypeByClass('App\\Entity\\User'))->toBe('user')
            ->and($typeMapper->getTypeByClass('App\\Entity\\Product'))->toBe('product')
            ->and($typeMapper->getTypeByClass('App\\Entity\\Order'))->toBe('order')
            ->and($typeMapper->getClassByType('user'))->toBe('App\\Entity\\User')
            ->and($typeMapper->getClassByType('product'))->toBe('App\\Entity\\Product')
            ->and($typeMapper->getClassByType('order'))->toBe('App\\Entity\\Order');
    });

    it('handles unmapped classes and types correctly', function () {
        $typeMap = [
            'user' => 'App\\Entity\\User',
        ];
        $typeMapper = new TypeMapper($typeMap);

        expect($typeMapper->getTypeByClass('App\\Entity\\Product'))->toBe('App\\Entity\\Product')
            ->and($typeMapper->getClassByType('product'))->toBe('product');
    });

    it('is case sensitive for mappings', function () {
        $typeMap = [
            'user' => 'App\\Entity\\User',
            'User' => 'App\\Entity\\UserCapital',
        ];
        $typeMapper = new TypeMapper($typeMap);

        expect($typeMapper->getClassByType('user'))->toBe('App\\Entity\\User')
            ->and($typeMapper->getClassByType('User'))->toBe('App\\Entity\\UserCapital')
            ->and($typeMapper->getTypeByClass('App\\Entity\\User'))->toBe('user')
            ->and($typeMapper->getTypeByClass('App\\Entity\\UserCapital'))->toBe('User');
    });

    it('handles empty type map', function () {
        $typeMapper = new TypeMapper([]);

        expect($typeMapper->getTypeByClass('App\\Entity\\User'))->toBe('App\\Entity\\User')
            ->and($typeMapper->getClassByType('user'))->toBe('user');
    });

    it('handles special characters in class names and types', function () {
        $typeMap = [
            'user-profile' => 'App\\Entity\\User\\Profile',
            'order_item' => 'App\\Entity\\Order\\Item',
        ];
        $typeMapper = new TypeMapper($typeMap);

        expect($typeMapper->getClassByType('user-profile'))->toBe('App\\Entity\\User\\Profile')
            ->and($typeMapper->getClassByType('order_item'))->toBe('App\\Entity\\Order\\Item')
            ->and($typeMapper->getTypeByClass('App\\Entity\\User\\Profile'))->toBe('user-profile')
            ->and($typeMapper->getTypeByClass('App\\Entity\\Order\\Item'))->toBe('order_item');
    });
});
