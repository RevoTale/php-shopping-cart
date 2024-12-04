<?php

namespace RevoTale\ShoppingCart\PromotionTemplates;

use RevoTale\ShoppingCart\CartInterface;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\Decimal;
use RevoTale\ShoppingCart\ModifiedCartData;
use RevoTale\ShoppingCart\PromotionInterface;

abstract class CartPercentageDiscount implements PromotionInterface
{
    abstract public function getCartId(): string;

    abstract public function getCartType(): string;

    abstract public function isEligible(CartInterface $cart): bool;

    abstract public function getDiscountMultiplier(): float;

    public function reduceItemSubtotal(ModifiedCartData $cart, CartItemInterface $item, Decimal $subTotal): Decimal
    {

        return $subTotal->mul(Decimal::fromFloat($this->getDiscountMultiplier()));
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