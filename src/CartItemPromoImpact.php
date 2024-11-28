<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

final readonly class CartItemPromoImpact
{
    public function __construct(
        public CartItemInterface $item,
        public Decimal           $priceImpact,
        public array $addedItems,
        public array $removedItems,
        public array $addedPromotions,
        public array $removedPromotions,
    )
    {
    }
}