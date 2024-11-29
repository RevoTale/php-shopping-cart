<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;

interface CartInterface
{
    public function addItem(CartItemInterface $item):void;
    public function removeItem(CartItemInterface $item):void;

    public function addPromotion(PromotionInterface $promotion):void;
    public function removePromotion(PromotionInterface $promotion):void;

    /**
     * @return list<CartItemInterface>
     */
    public function getItems():array;
    /**
     * @return list<PromotionInterface>
     */
    public function getPromotions():array;

    public function clearItems():void;

    public function getItemQuantity(CartItemInterface $item): int;

}