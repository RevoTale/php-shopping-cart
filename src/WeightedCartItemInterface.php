<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
interface WeightedCartItemInterface extends CartItemInterface
{
    /**
     * Get unit weight.
     */
    public function getWeight(): float;
}
