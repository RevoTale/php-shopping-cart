<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
readonly class CartContext
{

    /**
     * @param array<string|int,mixed> $data
     */
    public function __construct( private array $data = [])
    {

    }

    /**
     * @return array<string|int,mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
