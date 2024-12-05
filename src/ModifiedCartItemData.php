<?php

namespace RevoTale\ShoppingCart;

final readonly class ModifiedCartItemData
{
    public function __construct(
        public CartItemInterface $item,
        public int               $quantity
    )
    {
    }

    public function getPriceTotal(): Decimal
    {
        return Decimal::fromInteger($this->item->getUnitPrice())
            ->mul(
                Decimal::fromInteger($this->quantity)
            );
    }
}