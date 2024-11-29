<?php
declare(strict_types=1);
namespace RevoTale\ShoppingCart;

final class CartItemCounter
{
    public function __construct(public readonly CartItemInterface $item, public int $quantity = 0)
    {
    }

    public function getItem(): CartItemInterface
    {
        return $this->item;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}