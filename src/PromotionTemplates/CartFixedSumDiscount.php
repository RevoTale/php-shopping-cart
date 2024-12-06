<?php

namespace RevoTale\ShoppingCart\PromotionTemplates;

use RevoTale\ShoppingCart\CartInterface;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\CartItemSubTotalReducer;
use RevoTale\ShoppingCart\PromoCalculationsContext;
use RevoTale\ShoppingCart\Decimal;
use RevoTale\ShoppingCart\ModifiedCartData;
use RevoTale\ShoppingCart\PromotionInterface;

abstract class CartFixedSumDiscount implements PromotionInterface
{

    abstract public function getCartId(): string;

    abstract public function getCartType(): string;

    abstract public function isEligible(CartInterface $cart): bool;

    abstract public function getDiscountAmount(): float;

    public function reduceItemSubtotal(ModifiedCartData $cart, CartItemInterface $item, Decimal $subTotal, PromoCalculationsContext $context): Decimal
    {

        return $subTotal;
    }

    /**
     * @param list<CartItemSubTotalReducer> $items
     * @return Decimal
     */
    private function getDiscountMultiplier(array $items): Decimal
    {
        $total = Decimal::fromInteger(0);
        foreach ($items as $item) {
            $total = $total->add($item->subTotal,4);
        }
        return Decimal::fromInteger(1)->sub(Decimal::fromFloat($this->getDiscountAmount(),4)->div($total,10));
    }
    public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context,ModifiedCartData $data): void
    {

        $multiplier = $this->getDiscountMultiplier($items);
        foreach ($items as $item) {
            $item->subTotal = $item->subTotal->mul($multiplier,10);

        }
    }


    public function reduceItems(ModifiedCartData $cart, array $itemCounters): array
    {
        return $itemCounters;
    }


    public function reducePromotions(ModifiedCartData $cart, array $promotions): array
    {

        return $promotions;
    }
}