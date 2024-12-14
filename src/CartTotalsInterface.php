<?php
declare(strict_types=1);
namespace RevoTale\ShoppingCart;

interface CartTotalsInterface
{
    public function getTotal():Decimal;
    /**
     * @return list<CartItemInterface>
     */
    public function getItems():array;

    public function getItemQuantity(CartItemInterface $item):?int;
    /**
     * @return list<PromotionInterface>
     */
    public function getPromotions():array;
}