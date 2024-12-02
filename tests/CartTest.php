<?php
declare(strict_types=1);
namespace RevoTale\ShoppingCart\Tests;
use PHPUnit\Framework\TestCase;
use RevoTale\ShoppingCart\Cart;
use RevoTale\ShoppingCart\CartContext;
use RevoTale\ShoppingCart\CartItemInterface;

final class CartTest extends TestCase
{
    public function testBasis():void
    {

        $cart = new Cart();
        $item = new class implements CartItemInterface
        {

            public function getCartId(): string
            {
                return 'item_1';
            }

            public function getCartType(): string
            {
               return 'product';
            }

            public function setCartContext(CartContext $context): void
            {

            }

            public function getUnitPrice(): int
            {
               return 200;
            }
        };
        $cart->addItem($item);
        self::assertEquals(200,$cart->performTotals()->getTotal()->asInteger());
        $cart->addItem($item,2);
        self::assertEquals(600,$cart->performTotals()->getTotal()->asInteger());

    }
}