<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;

final class CartItemSubTotalReducer
{
    public function __construct(
        readonly public CartItemInterface $item,
        readonly public int               $quantity,
        private Decimal                    $subTotal
    )
    {
    }

    public function setSubTotal(Decimal $subTotal): void
    {
        $this->subTotal = $subTotal->isNegative()?Decimal::fromInteger(0):$subTotal;
    }

    public function getSubTotal(): Decimal
    {
        return $this->subTotal;
    }
}
