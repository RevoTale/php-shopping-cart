<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
class CartContext
{

    /**
     * @param array<string|int,mixed> $data
     */
    public function __construct(private Cart $cart, private array $data = [])
    {
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * @return array<string|int,mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
