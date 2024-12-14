<?php

declare(strict_types=1);

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
