<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart\Tests;

use PHPUnit\Framework\TestCase;
use RevoTale\ShoppingCart\BoundCartItemInterface;
use RevoTale\ShoppingCart\Cart;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\Decimal;
use RevoTale\ShoppingCart\MultipleBoundCartItemInterface;
use RevoTale\ShoppingCart\PromotionInterface;
use RevoTale\ShoppingCart\WeightedCartItemInterface;

class CartTest extends TestCase
{
    public function testAddsItems(): void
    {
        $cart = new Cart();

        // First item
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->method('getCartType')->willReturn('product');
        $item->method('getCartQuantity')->willReturn(2.0);
        $item->method('getUnitPrice')->willReturn(1.0);
        $item->method('getTaxRate')->willReturn(10.0);
        $item->expects($this->once())->method('setCartQuantity')->with(2.0);
        $item->expects($this->once())->method('setCartContext')->with($cart->getContext());

        $cart->addItem($item, 2.0);

        // Second item
        $item2 = $this->createMock(CartItemInterface::class);
        $item2->method('getCartId')->willReturn('B');
        $item2->method('getCartType')->willReturn('product');
        $item2->method('getCartQuantity')->willReturn(1.0);
        $item2->method('getUnitPrice')->willReturn(0.825);
        $item2->method('getTaxRate')->willReturn(20.0);
        $item2->expects($this->once())->method('setCartQuantity')->with(1.0);
        $item2->expects($this->once())->method('setCartContext')->with($cart->getContext());

        $cart->addItem($item2);

        $this->assertEquals(2, $cart->countItems());
        $this->assertEquals(2.0, $cart->getItem('A')->getCartQuantity());
        $this->assertEquals(1.0, $cart->getItem('B')->getCartQuantity());
    }

    public function testSetsItems(): void
    {
        $cart = new Cart(roundingDecimals: 2);

        // Set up the first item
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->method('getCartType')->willReturn('product');
        $item->method('getCartQuantity')->willReturn(3.0);
        $item->method('getUnitPrice')->willReturn(1.0);
        $item->method('getTaxRate')->willReturn(10.0);
        $item->expects($this->atLeastOnce())->method('setCartQuantity')->with(3.0);
        $item->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());

        // Set up the second item
        $item2 = $this->createMock(CartItemInterface::class);
        $item2->method('getCartId')->willReturn('B');
        $item2->method('getCartType')->willReturn('product');
        $item2->method('getCartQuantity')->willReturn(1.0);
        $item2->method('getUnitPrice')->willReturn(0.825);
        $item2->method('getTaxRate')->willReturn(20.0);
        $item2->expects($this->once())->method('setCartQuantity')->with(1.0);
        $item2->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());

        // Set the items in the cart
        $cart->setItems([$item, $item2]);

        // Assert that the total is as expected
        $this->assertTrue($cart->getTotal()->equals(Decimal::fromFloat(4.29)));
    }

    public function testChangesItemQuantity(): void
    {
        $cart = new Cart();

        // Variable to store the quantity
        $quantity = 2.0;

        // Item mock
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->method('getCartType')->willReturn('product');
        $item->method('getUnitPrice')->willReturn(1.0);
        $item->method('getTaxRate')->willReturn(10.0);
        $item->method('setCartQuantity')
            ->willReturnCallback(function (float $qty) use (&$quantity) {
                $quantity = $qty;
            });
        $item->method('getCartQuantity')
            ->willReturnCallback(function () use (&$quantity) {
                return $quantity;
            });
        $item->expects($this->any())->method('setCartQuantity');
        $item->expects($this->once())->method('setCartContext')->with($cart->getContext());

        $cart->addItem($item, 2.0);

        // Call the method under test
        $cart->setItemQuantity('A', 7.0);

        // Assert that the item's quantity is updated in the cart
        $this->assertEquals(7.0, $cart->getItem('A')->getCartQuantity());
    }

    public function testRemovesItem(): void
    {
        $cart = new Cart();

        // Item
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->expects($this->once())->method('setCartQuantity')->with(2.0);
        $item->expects($this->once())->method('setCartContext')->with($cart->getContext());

        $cart->addItem($item, 2.0);

        $this->assertTrue($cart->hasItem('A'));

        $cart->removeItem('A');

        $this->assertFalse($cart->hasItem('A'));
    }

    public function testMergesItemsOfSameId(): void
    {
        $cart = new Cart();

        // Existing item in cart
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->method('getCartType')->willReturn('product');
        $item->method('getCartQuantity')->willReturn(2.0);
        $item->expects($this->any())->method('setCartQuantity');
        $item->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($item, 2.0);

        // New item with same ID
        $item3 = $this->createMock(CartItemInterface::class);
        $item3->method('getCartId')->willReturn('A');
        $item3->method('getCartType')->willReturn('product');
        $item->expects($this->atLeastOnce())->method('getCartQuantity');
        $item->expects($this->once())->method('setCartQuantity')->with(4.3);

        $cart->addItem($item3, 2.3);
    }

    public function testChecksEmptyStateCorrectly(): void
    {
        $cart = new Cart();

        // Add an item
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->method('getCartType')->willReturn('product');
        $item->method('getCartQuantity')->willReturn(3.0);
        $item->method('getUnitPrice')->willReturn(1.0);
        $item->method('getTaxRate')->willReturn(10.0);
        $cart->addItem($item);

        $this->assertFalse($cart->isEmpty());

        $cart->clear();

        $this->assertTrue($cart->isEmpty());
    }

    public function testCountsTotalsForGrossPricesCorrectly(): void
    {
        $cart = new Cart(roundingDecimals: 2);

        // Item A
        $itemA = $this->createMock(CartItemInterface::class);
        $itemA->method('getCartId')->willReturn('A');
        $itemA->method('getCartType')->willReturn('product');
        $itemA->method('getCartQuantity')->willReturn(2.0);
        $itemA->method('getUnitPrice')->willReturn(1.0);
        $itemA->method('getTaxRate')->willReturn(10.0);
        $itemA->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemA);

        // Item B
        $itemB = $this->createMock(CartItemInterface::class);
        $itemB->method('getCartId')->willReturn('B');
        $itemB->method('getCartType')->willReturn('product');
        $itemB->method('getCartQuantity')->willReturn(1.0);
        $itemB->method('getUnitPrice')->willReturn(0.825);
        $itemB->method('getTaxRate')->willReturn(20.0);
        $itemB->expects($this->atLeastOnce())->method('setCartQuantity')->with(1.0);
        $itemB->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemB);
        $this->assertTrue($cart->getSubtotal()->equals(Decimal::fromFloat(2.82)));
        $this->assertTrue($cart->getTotal()->equals(Decimal::fromFloat(3.19)));

        $taxes = $cart->getTaxes();
        $this->assertTrue($taxes[10]->equals(Decimal::fromFloat(0.2)));
        $this->assertTrue($taxes[20]->equals(Decimal::fromFloat(0.17)));

        $taxBases = $cart->getTaxBases();
        $this->assertTrue($taxBases[10]->equals(Decimal::fromFloat(2.0)));
        $this->assertTrue($taxBases[20]->equals(Decimal::fromFloat(0.82)));

        $taxTotals = $cart->getTaxTotals();
        $this->assertTrue($taxTotals[10]->equals(Decimal::fromFloat(2.2)));
        $this->assertTrue($taxTotals[20]->equals(Decimal::fromFloat(0.99)));
    }

    public function testCountsTotalsForNetPricesCorrectly(): void
    {
        $cart = new Cart(roundingDecimals: 2);

        // Item A
        $itemA = $this->createMock(CartItemInterface::class);
        $itemA->method('getCartId')->willReturn('A');
        $itemA->method('getCartType')->willReturn('product');
        $itemA->method('getCartQuantity')->willReturn(2.0);
        $itemA->method('getUnitPrice')->willReturn(1.0);
        $itemA->method('getTaxRate')->willReturn(10.0);
        $itemA->expects($this->atLeastOnce())->method('setCartQuantity')->with(2.0);
        $itemA->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemA,2.0);

        // Item B
        $itemB = $this->createMock(CartItemInterface::class);
        $itemB->method('getCartId')->willReturn('B');
        $itemB->method('getCartType')->willReturn('product');
        $itemB->method('getCartQuantity')->willReturn(1.0);
        $itemB->method('getUnitPrice')->willReturn(0.825);
        $itemB->method('getTaxRate')->willReturn(20.0);
        $itemB->expects($this->atLeastOnce())->method('setCartQuantity')->with(1.0);
        $itemB->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemB);

        $cart->setPricesWithVat(false);
        $this->assertTrue($cart->getSubtotal()->equals(Decimal::fromFloat(2.83)));
        $this->assertTrue($cart->getTotal()->equals(Decimal::fromFloat(3.2)));

        $taxes = $cart->getTaxes();
        $this->assertTrue($taxes[10]->equals(Decimal::fromFloat(0.2)));
        $this->assertTrue($taxes[20]->equals(Decimal::fromFloat(0.17)));

        $taxBases = $cart->getTaxBases();
        $this->assertTrue($taxBases[10]->equals(Decimal::fromFloat(2.0)));
        $this->assertTrue($taxBases[20]->equals(Decimal::fromFloat(0.83)));

        $taxTotals = $cart->getTaxTotals();
        $this->assertTrue($taxTotals[10]->equals(Decimal::fromFloat(2.2)));
        $this->assertTrue($taxTotals[20]->equals(Decimal::fromFloat(1.0)));
    }

    public function testHandlesPromotions(): void
    {
        $cart = new Cart();

        // Add an item to trigger promotions
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->expects($this->once())->method('setCartQuantity')->with(1.0);
        $item->expects($this->once())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($item);

        $promotion1 = $this->createMock(PromotionInterface::class);
        $promotion2 = $this->createMock(PromotionInterface::class);

        $promotion1->method('isEligible')->willReturn(true);
        $promotion2->method('isEligible')->willReturn(false);

        $promotion1->expects($this->once())->method('beforeApply')->with($cart);
        $promotion2->expects($this->once())->method('beforeApply')->with($cart);
        $promotion1->expects($this->once())->method('apply')->with($cart);
        $promotion1->expects($this->once())->method('afterApply')->with($cart);
        $promotion2->expects($this->once())->method('afterApply')->with($cart);

        $cart->setPromotions([$promotion1, $promotion2]);

        $cart->removeItem('A');
    }

    public function testCanSetRounding(): void
    {
        $cart = new Cart();

        // Add items
        $itemA = $this->createMock(CartItemInterface::class);
        $itemA->method('getCartId')->willReturn('A');
        $itemA->method('getCartType')->willReturn('product');
        $itemA->method('getUnitPrice')->willReturn(1.0);
        $itemA->method('getTaxRate')->willReturn(10.0);
        $itemA->method('getCartQuantity')->willReturn(2.0);
        $itemA->expects($this->once())->method('setCartQuantity')->with(2.0);
        $itemA->expects($this->once())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemA);

        $itemB = $this->createMock(CartItemInterface::class);
        $itemB->method('getCartId')->willReturn('B');
        $itemB->method('getCartType')->willReturn('product');
        $itemB->method('getUnitPrice')->willReturn(0.825);
        $itemB->method('getTaxRate')->willReturn(20.0);
        $itemB->method('getCartQuantity')->willReturn(1.0);
        $itemB->expects($this->once())->method('setCartQuantity')->with(1.0);
        $itemB->expects($this->once())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemB);

        $cart->setTotalRounding(fn(Decimal $total) => $total->round());

        $this->assertTrue($cart->getTotal()->equals(Decimal::fromFloat(3.0)));
        $this->assertTrue($cart->getRoundingAmount()->equals(Decimal::fromFloat(-0.19)));
    }

    // Continue rewriting the remaining test methods similarly, ensuring local carts and items are used.

    // ...
}