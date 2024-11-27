<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;
interface MultipleBoundCartItemInterface extends CartItemInterface
{
    /**
     * Get bound item cart ids.
     *
     * @return list<string>
     *
     */
    public function getBoundItemCartIds(): array;
}
