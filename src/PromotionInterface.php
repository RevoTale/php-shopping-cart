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
    public function reduceItemSubtotal(CartInterface $cart,CartItemInterface $item): Decimal;

    /**
     * @param list<CartItemInterface> $items
     * @return list<CartItemInterface>
     */
    public function reduceItems(CartInterface $cart,array $items): array;

    /**
     * @param list<PromotionInterface> $items
     * @return list<PromotionInterface>
     */
    public function reducePromotions(CartInterface $cart,array $items): array;

}
