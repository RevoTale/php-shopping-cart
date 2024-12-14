<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;

final  class CartItemSubTotalReducer
{
    public function __construct(
        readonly public CartItemInterface $item,
        readonly public int               $quantity,
        public Decimal                    $subTotal
    )
    {
    }
}
