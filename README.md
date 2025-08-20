# Doctrine Lazy JSON ODM Bundle

A high-performance Symfony bundle that provides a Doctrine JSON ODM (Object Document Mapper) with advanced lazy loading capabilities. This bundle stores complex object structures as JSON in database fields while providing transparent lazy loading to improve performance.

[![Tests](https://github.com/vuryss/doctrine-lazy-json-odm/actions/workflows/tests.yml/badge.svg)](https://github.com/vuryss/doctrine-lazy-json-odm/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/symfony-%5E6.4%7C%5E7.0-green)](https://symfony.com/)

## Features

- **🚀 Lazy Loading**: Uses PHP 8.4 lazy objects for deferred JSON deserialization
- **⚡ Serializer**: Uses vuryss/serializer v2 for maximum performance
- **🎯 Type Mapping**: Map class names to aliases for storage optimization
- **📦 Collection Support**: Lazy-loaded arrays of objects with transparent usage
- **🧪 Comprehensive Testing**: Extensive test coverage with Pest framework
- **📊 Performance Optimized**: Significant memory and CPU savings for unused data

## Requirements

- PHP 8.4 or higher
- Symfony 7.0+
- Doctrine ORM 3.4+

## Installation

Install the bundle via Composer:

```bash
composer require vuryss/doctrine-lazy-json-odm
```

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    \Vuryss\DoctrineJsonOdm\DoctrineLazyJsonOdmBundle::class => ['all' => true],
];
```

## Configuration

Create a configuration file `config/packages/doctrine_lazy_json_odm.yaml`:

```yaml
doctrine_json_odm:
    type_map:
        user: 'App\Entity\User'
        product: 'App\Entity\Product'
        order: 'App\Entity\Order'
    cache_pool: 'app.doctrine_lazy_json_odm.cache_pool'
```

## Usage

// TODO

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/pest

# Run specific test suites
vendor/bin/pest tests/Unit/
vendor/bin/pest tests/Integration/

# Run with coverage
vendor/bin/pest --coverage
```

## Code Quality

The bundle maintains high code quality standards:

```bash
# PHP CS Fixer
vendor/bin/php-cs-fixer fix --using-cache=no

# PHPStan
vendor/bin/phpstan analyse -v
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for your changes
4. Ensure all tests pass
5. Run code quality tools
6. Submit a pull request

## License

This bundle is released under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

- Inspired by [dunglas/doctrine-json-odm](https://github.com/dunglas/doctrine-json-odm)
- Uses [vuryss/serializer](https://github.com/vuryss/serializer) for high-performance serialization
- Built with PHP 8.4 lazy objects feature
