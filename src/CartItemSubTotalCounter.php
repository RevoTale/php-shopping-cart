<?php

namespace RevoTale\ShoppingCart;

//Maybe this is bad idem consider inner algorithm context variablled
//TODO review maybe delete it
final class CartItemSubTotalCounter
{
    /**
     * @var list<CartItemPromoImpact>
     */
    private array $promotionImpacts = [];

    public function __construct(
        public readonly CartItemInterface $item,
        private Decimal                   $subTotal,
    )
    {
    }

    public function getSubTotal(): Decimal
    {
        return $this->subTotal;
    }

    public function setSubTotal(Decimal $amount, PromotionInterface $promotion):void
    {
        $diff = $amount->sub($this->subTotal);
        $this->promotionImpacts[] = new CartItemPromoImpact(item: $this->item, promotion: $promotion, priceImpact: $diff);
        $this->subTotal = $amount;
    }

    /**
     * @return list<CartItemPromoImpact>
     */
    public function getPromotionImpacts(): array
    {
        return $this->promotionImpacts;
    }
}