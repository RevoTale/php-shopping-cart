<?php
declare(strict_types=1);
namespace RevoTale\ShoppingCart;

interface CartResultInterface
{
    public function getTotal():Decimal;
    /**
     * @return list<CartItemInterface>
     */
    public function getItems():array;
    /**
     * @return list<PromotionInterface>
     */
    public function getPromotions():array;

    public function getSubTotalForItem(CartItemInterface $item):Decimal;

    public function getSubTotalForPromotion(PromotionInterface $promotion):Decimal;
}