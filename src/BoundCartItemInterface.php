<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;

interface BoundCartItemInterface extends CartItemInterface
{
    /**
     * Get bound item cart id.
     * @return list<string>
     */
    public function getBoundItemCartIds(): array;
}
