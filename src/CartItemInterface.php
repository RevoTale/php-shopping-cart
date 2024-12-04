<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
interface CartItemInterface
{
    /**
     * Get item identifier.
     */
    public function getCartId(): string;

    /**
     * Get type of the item.
     */
    public function getCartType(): string;

    /**
     * Get unit price in minimal currency unit.
     */
    public function getUnitPrice(): int;


}
