<?php

declare(strict_types=1);

namespace Vuryss\DoctrineJsonOdm\Tests\Fixtures;

/**
 * Test entity for unit tests.
 */
class TestEntity
{
    public function __construct(
        public string $name = '',
        public int $age = 0,
        public bool $active = true,
        public ?string $email = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
