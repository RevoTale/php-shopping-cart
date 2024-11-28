<?php
declare(strict_types=1);
namespace RevoTale\ShoppingCart;

final readonly class CartItemSubTotal
{
    public function __construct(
        public CartItemInterface $item,
        public int $quantity,
        public Decimal $subTotalBeforePromo,
        public Decimal $subTotalAfterPromo
    )
    {
    }
}