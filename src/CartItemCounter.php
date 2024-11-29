<?php

namespace RevoTale\ShoppingCart;

final class CartItemCounter
{
    public function __construct(public readonly CartItemInterface $item, public int $quantity = 0)
    {
    }
}