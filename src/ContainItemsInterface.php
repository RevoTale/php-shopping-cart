<?php
declare(strict_types=1);
namespace RevoTale\ShoppingCart;

interface ContainItemsInterface
{
    /**
     * @return list<CartItemInterface>
     */
    public function getItems():array;

    public function getItemQuantity(CartItemInterface $item):?int;
}