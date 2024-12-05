<?php

namespace RevoTale\ShoppingCart;

final class Context
{
    /**
     * @var array<string,mixed>
     */
    private array $data = [];

    public function __construct()
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setValue(string $key, mixed $data): void
    {
        $this->data[$key] = $data;
    }

    public function getValue(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function hasValue(string $key): bool
    {
        return isset($this->data[$key]);
    }
}