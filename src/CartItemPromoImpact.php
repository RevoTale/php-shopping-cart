<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

final readonly class CartItemPromoImpact
{
    public function __construct(
        public CartItemInterface $item,
        public PromotionInterface $promotion,
        public Decimal           $priceImpact,


    )
    {
    }
}