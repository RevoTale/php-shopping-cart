<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;

interface BoundCartItemInterface extends CartItemInterface
{
    /**
     * Get bound item cart id.
     */
    public function getBoundItemCartId(): string;

    /**
     * Update quantity automatically.
     */
    public function updateCartQuantityAutomatically(): bool;
}
