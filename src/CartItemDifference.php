<?php

namespace RevoTale\ShoppingCart;

final readonly class CartItemDifference
{
    public function __construct(
        public CartItemInterface $item,
        public int $difference
    )
    {
    }
}