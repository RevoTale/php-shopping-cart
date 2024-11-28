<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
/**
 * @template T implements CartInterface
 */
class CartContext
{

    /**
     * @param array<string|int,mixed> $data
     * @param T $cart
     */
    public function __construct(private CartInterface $cart, private array $data = [])
    {
    }

    public function getCart(): CartInterface
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
