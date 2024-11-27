<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
class CartContext
{

    /**
     * @param list<mixed> $data
     */
    public function __construct(private Cart $cart, private array $data = [])
    {
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * @return list<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
