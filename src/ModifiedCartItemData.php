<?php

namespace RevoTale\ShoppingCart;

final readonly class ModifiedCartItemData
{
    public function __construct(
        public CartItemInterface $item,
        public int $quantity,
    )
    {
    }
}