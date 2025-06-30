# Doctrine Lazy JSON ODM Bundle

A high-performance Symfony bundle that provides a Doctrine JSON ODM (Object Document Mapper) with advanced lazy loading capabilities and dual serializer support. This bundle stores complex object structures as JSON in database fields while providing transparent lazy loading to improve performance.

[![Tests](https://github.com/vuryss/doctrine-lazy-json-odm/actions/workflows/tests.yml/badge.svg)](https://github.com/vuryss/doctrine-lazy-json-odm/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/symfony-%5E6.4%7C%5E7.0-green)](https://symfony.com/)

## Features

- **🚀 Lazy Loading**: Uses PHP 8.4 lazy objects for deferred JSON deserialization
- **⚡ Dual Serializer Support**: Choose between Symfony Serializer or vuryss/serializer
- **🎯 Type Mapping**: Map class names to aliases for storage optimization
- **📦 Collection Support**: Lazy-loaded arrays of objects with transparent usage
- **🔧 Symfony Integration**: Full Symfony bundle with configuration support
- **🧪 Comprehensive Testing**: Extensive test coverage with Pest framework
- **📊 Performance Optimized**: Significant memory and CPU savings for unused data

## Requirements

- PHP 8.4 or higher
- Symfony 6.4+ or 7.0+
- Doctrine ORM 2.15+ or 3.0+

## Installation

Install the bundle via Composer:

```bash
composer require vuryss/doctrine-lazy-json-odm
```

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    Vuryss\DoctrineJsonOdm\Bundle\DoctrineJsonOdmBundle::class => ['all' => true],
];
```

## Configuration

Create a configuration file `config/packages/doctrine_json_odm.yaml`:

```yaml
doctrine_json_odm:
    # Choose serializer: symfony or vuryss
    serializer: symfony
    
    # Enable lazy loading (default: true)
    lazy_loading: true
    
    # Lazy loading strategy: ghost or proxy (default: ghost)
    lazy_strategy: ghost
    
    # Map class names to type aliases for storage optimization
    type_map:
        user: 'App\Entity\User'
        product: 'App\Entity\Product'
        order: 'App\Entity\Order'
    
    # Default serialization context
    serialization_context:
        groups: ['api']
    
    # Default deserialization context
    deserialization_context:
        groups: ['api']
    
    # Symfony serializer configuration
    symfony_serializer:
        normalizers: []
        denormalizers: []
        encoders: []
    
    # Vuryss serializer configuration
    vuryss_serializer:
        normalizers: []
        denormalizers: []
        enable_cache: true
```

## Usage

### Basic Entity Setup

Define your entities with JSON document fields:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'json_document')]
    private mixed $data = null;

    #[ORM\Column(type: 'json_document')]
    private mixed $metadata = null;

    // Getters and setters...
}
```

### Storing Objects

```php
<?php

use App\Entity\Document;
use App\Entity\User;

// Create a user object
$user = new User('John Doe', 'john@example.com', 30);

// Store it in a document
$document = new Document();
$document->setData($user);

$entityManager->persist($document);
$entityManager->flush();
```

### Lazy Loading in Action

```php
<?php

// Retrieve the document
$document = $entityManager->find(Document::class, 1);

// The data is still JSON at this point - no deserialization yet
$userData = $document->getData();

// This triggers lazy loading and deserializes the JSON
$userName = $userData->getName(); // Now the object is fully loaded

// Subsequent property access is immediate
$userEmail = $userData->getEmail();
```

### Working with Collections

```php
<?php

// Store a collection of objects
$users = [
    new User('John', 'john@example.com', 30),
    new User('Jane', 'jane@example.com', 25),
    new User('Bob', 'bob@example.com', 35),
];

$document = new Document();
$document->setData($users);
$entityManager->persist($document);
$entityManager->flush();

// Retrieve and use the collection
$document = $entityManager->find(Document::class, 1);
$userCollection = $document->getData(); // LazyCollection instance

// Individual items are lazy-loaded on access
foreach ($userCollection as $user) {
    echo $user->getName(); // Each user is deserialized when accessed
}

// Collection behaves like a regular array
$firstUser = $userCollection[0];
$userCollection[] = new User('Alice', 'alice@example.com', 28);
unset($userCollection[1]);
```

## Advanced Features

### Type Mapping

Use type aliases to reduce JSON storage size:

```yaml
# config/packages/doctrine_json_odm.yaml
doctrine_json_odm:
    type_map:
        user: 'App\Entity\User'
        product: 'App\Entity\Product'
```

This stores `"#type": "user"` instead of `"#type": "App\\Entity\\User"` in the JSON.

### Serializer Selection

Choose between Symfony Serializer and vuryss/serializer based on your needs:

```yaml
# For maximum compatibility
doctrine_json_odm:
    serializer: symfony

# For maximum performance
doctrine_json_odm:
    serializer: vuryss
```

### Lazy Loading Strategies

Choose between two lazy loading strategies:

- **Ghost**: In-place initialization (default, more memory efficient)
- **Proxy**: Proxy to real instance (better for complex inheritance)

```yaml
doctrine_json_odm:
    lazy_strategy: ghost  # or 'proxy'
```

### Disabling Lazy Loading

For debugging or specific use cases:

```yaml
doctrine_json_odm:
    lazy_loading: false
```

## Performance Benefits

The lazy loading feature provides significant performance improvements:

- **Memory Usage**: Objects are not deserialized until accessed
- **CPU Usage**: Avoid unnecessary JSON parsing for unused data
- **Database Efficiency**: Store complex objects as optimized JSON
- **Scalability**: Handle large collections without memory issues

## JSON Structure

The bundle stores objects with type metadata in a flat structure:

```json
{
    "#type": "user",
    "name": "John Doe",
    "email": "john@example.com",
    "age": 30,
    "active": true
}
```

Collections are stored as arrays:

```json
[
    {
        "#type": "user",
        "name": "John Doe",
        "email": "john@example.com",
        "age": 30
    },
    {
        "#type": "user", 
        "name": "Jane Doe",
        "email": "jane@example.com",
        "age": 25
    }
]
```

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
