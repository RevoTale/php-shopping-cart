<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

interface CartInterface extends ContainItemsInterface
{
    public function addItem(CartItemInterface $item):void;

    public function removeItem(CartItemInterface $item):void;

    public function addPromotion(PromotionInterface $promotion):void;

    public function removePromotion(PromotionInterface $promotion):void;

    /**
     * @return list<PromotionInterface>
     */
    public function getPromotions():array;

    public function clearItems():void;


}