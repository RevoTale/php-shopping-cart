<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart\PromotionTemplates;

use RevoTale\ShoppingCart\CartInterface;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\PromoCalculationsContext;
use RevoTale\ShoppingCart\Decimal;
use RevoTale\ShoppingCart\ModifiedCartData;
use RevoTale\ShoppingCart\PromotionInterface;

abstract class CartPercentageDiscount implements PromotionInterface
{
    abstract public function getCartId(): string;

    abstract public function getCartType(): string;

    abstract public function isEligible(CartInterface $cart): bool;

    abstract public function getDiscountMultiplier(): float;

    public function reduceItemSubtotal(ModifiedCartData $cart, CartItemInterface $item, Decimal $subTotal, PromoCalculationsContext $context): Decimal
    {

        return $subTotal->mul(Decimal::fromFloat($this->getDiscountMultiplier()),4);
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
