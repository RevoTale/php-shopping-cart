<?php

namespace RevoTale\ShoppingCart;

final class CartPromoImpact
{
    /**
     * @param list<CartItemDifference> $cartItemsDiff
     * @param list<array{item:PromotionInterface,diff:int}> $promotionsDiff
     */
    public function __construct(
        public PromotionInterface $promotion,
        public array              $cartItemsDiff = [],
        public array              $promotionsDiff = [],
    )
    {
    }

    /**
     * @return list<CartItemDifference>
     */
    public function getCartItemsDiff(): array
    {
        return $this->cartItemsDiff;
    }


    /**
     * @return list<PromotionInterface>
     */
    public function getAddedPromos(): array
    {
        $result = [];
        foreach ($this->promotionsDiff as $promotionDiff) {
            if ($promotionDiff['diff']>0) {
                $result[] = $promotionDiff['item'];
            }
        }
        return $result;
    }
    /**
     * @return list<PromotionInterface>
     */
    public function getRemovedPromos(): array
    {
        $result = [];
        foreach ($this->promotionsDiff as $promotionDiff) {
            if ($promotionDiff['diff']<0) {
                $result[] = $promotionDiff['item'];
            }
        }
        return $result;
    }
}