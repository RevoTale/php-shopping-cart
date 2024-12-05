<?php

namespace RevoTale\ShoppingCart\PromotionTemplates;

use RevoTale\ShoppingCart\CartInterface;
use RevoTale\ShoppingCart\CartItemInterface;
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

        $multiplier = $this->getDiscountMultiplier($cart);

        return $subTotal->mul($multiplier);
    }

    private function getDiscountMultiplier(ModifiedCartData $cartData): Decimal
    {
        $total = Decimal::fromInteger(0);
        foreach ($cartData->items as $item) {
            $total = $total->add($item->getPriceTotal());
        }
        return Decimal::fromFloat($this->getDiscountAmount())->div($total);
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