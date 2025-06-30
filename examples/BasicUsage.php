<?php

declare(strict_types=1);

/**
 * Basic usage example for Doctrine Lazy JSON ODM Bundle.
 *
 * This example demonstrates how to use the bundle to store and retrieve
 * complex objects as JSON with lazy loading capabilities.
 */

namespace App\Example;

use Doctrine\ORM\Mapping as ORM;

// Example entities
#[ORM\Entity]
class User
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public int $age = 0,
        public bool $active = true,
        public array $preferences = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getPreferences(): array
    {
        return $this->preferences;
    }
}

#[ORM\Entity]
class Product
{
    public function __construct(
        public string $name = '',
        public float $price = 0.0,
        public string $category = '',
        public array $attributes = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}

// Document entity that stores JSON data
#[ORM\Entity]
#[ORM\Table(name: 'documents')]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    // This field will store objects as JSON with lazy loading
    #[ORM\Column(type: 'json_document')]
    private mixed $data = null;

    // This field can store collections of objects
    #[ORM\Column(type: 'json_document', nullable: true)]
    private mixed $items = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getItems(): mixed
    {
        return $this->items;
    }

    public function setItems(mixed $items): self
    {
        $this->items = $items;

        return $this;
    }
}

// Usage examples
class ExampleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function storeUserExample(): void
    {
        // Create a user object
        $user = new User(
            name: 'John Doe',
            email: 'john@example.com',
            age: 30,
            active: true,
            preferences: ['theme' => 'dark', 'language' => 'en']
        );

        // Store it in a document
        $document = new Document();
        $document->setTitle('User Profile');
        $document->setData($user);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        echo 'User stored with ID: '.$document->getId()."\n";
    }

    public function retrieveUserExample(int $documentId): void
    {
        // Retrieve the document
        $document = $this->entityManager->find(Document::class, $documentId);

        if (!$document) {
            echo "Document not found\n";

            return;
        }

        // At this point, the data is still JSON - no deserialization yet
        $userData = $document->getData();

        echo 'Document title: '.$document->getTitle()."\n";

        // This triggers lazy loading and deserializes the JSON
        echo 'User name: '.$userData->getName()."\n";

        // Subsequent property access is immediate (no more deserialization)
        echo 'User email: '.$userData->getEmail()."\n";
        echo 'User age: '.$userData->getAge()."\n";
        echo 'User active: '.($userData->isActive() ? 'Yes' : 'No')."\n";
        echo 'User preferences: '.json_encode($userData->getPreferences())."\n";
    }

    public function storeCollectionExample(): void
    {
        // Create a collection of products
        $products = [
            new Product('Laptop', 999.99, 'Electronics', ['brand' => 'TechCorp', 'warranty' => '2 years']),
            new Product('Mouse', 29.99, 'Electronics', ['brand' => 'TechCorp', 'wireless' => true]),
            new Product('Keyboard', 79.99, 'Electronics', ['brand' => 'TechCorp', 'mechanical' => true]),
        ];

        // Store the collection
        $document = new Document();
        $document->setTitle('Product Catalog');
        $document->setItems($products);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        echo 'Product collection stored with ID: '.$document->getId()."\n";
    }

    public function retrieveCollectionExample(int $documentId): void
    {
        // Retrieve the document
        $document = $this->entityManager->find(Document::class, $documentId);

        if (!$document) {
            echo "Document not found\n";

            return;
        }

        // Get the lazy collection
        $productCollection = $document->getItems();

        echo 'Document title: '.$document->getTitle()."\n";
        echo 'Number of products: '.count($productCollection)."\n";

        // Individual items are lazy-loaded on access
        foreach ($productCollection as $index => $product) {
            echo "Product {$index}: ".$product->getName().' - $'.$product->getPrice()."\n";
            // Each product is deserialized only when accessed
        }

        // Collection behaves like a regular array
        $firstProduct = $productCollection[0];
        echo 'First product category: '.$firstProduct->getCategory()."\n";

        // You can modify the collection
        $newProduct = new Product('Headphones', 149.99, 'Electronics', ['brand' => 'AudioCorp']);
        $productCollection[] = $newProduct;

        echo 'Added new product. Total products: '.count($productCollection)."\n";
    }

    public function performanceExample(): void
    {
        // Create a large collection to demonstrate performance benefits
        $largeCollection = [];
        for ($i = 0; $i < 1000; ++$i) {
            $largeCollection[] = new User(
                name: "User {$i}",
                email: "user{$i}@example.com",
                age: rand(18, 80),
                active: 1 === rand(0, 1),
                preferences: ['setting' => rand(1, 100)]
            );
        }

        $document = new Document();
        $document->setTitle('Large User Collection');
        $document->setItems($largeCollection);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        echo "Large collection stored. Retrieving...\n";

        // Clear entity manager to ensure fresh load
        $this->entityManager->clear();

        $start = microtime(true);

        // Retrieve the document
        $document = $this->entityManager->find(Document::class, $document->getId());
        $collection = $document->getItems();

        $loadTime = microtime(true) - $start;
        echo 'Collection loaded in: '.round($loadTime * 1000, 2)."ms\n";
        echo 'Collection size: '.count($collection)."\n";

        $start = microtime(true);

        // Access only the first 10 items
        for ($i = 0; $i < 10; ++$i) {
            $user = $collection[$i];
            echo "User {$i}: ".$user->getName()."\n";
        }

        $accessTime = microtime(true) - $start;
        echo 'First 10 items accessed in: '.round($accessTime * 1000, 2)."ms\n";
        echo "Remaining 990 items were never deserialized!\n";
    }
}
