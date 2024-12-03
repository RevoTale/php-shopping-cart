<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
/**
 * Can be used for free delivery,free product,taxes, discounts and bound item
 */
interface PromotionInterface
{
    /**
     * If this promotion is eligible.
     */
    public function isEligible(CartInterface $cart): bool;
    /**
     * Apply promotion. Called only if promotion is eligible.
     */
    public function reduceItemSubtotal(ModifiedCartData $cart,CartItemInterface $item,Decimal $subTotal): Decimal;

    /**
     * @param list<CartItemCounter> $itemCounters
     * @return list<CartItemCounter>
     */
    public function reduceItems(ModifiedCartData $cart,array $itemCounters): array;

    /**
     * @param ModifiedCartData $cart
     * @param list<PromotionInterface> $promotions
     * @return list<PromotionInterface>
     */
    public function reducePromotions(ModifiedCartData $cart,array $promotions): array;
    public function getCartId():string;
    public function getCartType(): string;

}
