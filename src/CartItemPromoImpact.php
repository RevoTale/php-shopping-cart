<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

final readonly class CartItemPromoImpact
{
    /**
     * @param list<CartItemCounter> $addedItems
     * @param list<CartItemCounter> $removedItems
     * @param list<PromotionInterface> $addedPromotions
     * @param list<PromotionInterface> $removedPromotions
     */
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