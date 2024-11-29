<?php

namespace RevoTale\ShoppingCart;

final class CartPromoImpact
{
    /**
     * @param list<CartItemCounter> $addedItems
     * @param list<CartItemCounter> $removedItems
     * @param list<PromotionInterface> $addedPromotions
     * @param list<PromotionInterface> $removedPromotions
     */
    public function __construct(
        public PromotionInterface $promotion,
        public array $addedItems,
        public array $removedItems,
        public array $addedPromotions,
        public array $removedPromotions,
    )
    {
    }
}