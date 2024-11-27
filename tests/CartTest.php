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
    private Cart $cart;
    private CartItemInterface $item;
    private CartItemInterface $item2;

    protected function setUp(): void
    {
        $this->cart = new Cart();

        // first item
        $this->item = $this->createMock(CartItemInterface::class);
        $this->item->method('getCartId')->willReturn('A');
        $this->item->method('getCartType')->willReturn('product');
        $this->item->method('getCartQuantity')->willReturn(2.0);
        $this->item->method('getUnitPrice')->willReturn(1.0);
        $this->item->method('getTaxRate')->willReturn(10.0);
        $this->item->expects($this->once())->method('setCartQuantity')->with(2);
        $this->item->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($this->item, 2);

        // second item
        $this->item2 = $this->createMock(CartItemInterface::class);
        $this->item2->method('getCartId')->willReturn('B');
        $this->item2->method('getCartType')->willReturn('product');
        $this->item2->method('getCartQuantity')->willReturn(1.0);
        $this->item2->method('getUnitPrice')->willReturn(0.825);
        $this->item2->method('getTaxRate')->willReturn(20.0);
        $this->item2->expects($this->once())->method('setCartQuantity')->with(1);
        $this->item2->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($this->item2);
    }

    public function testAddsItems(): void
    {
        $this->assertEquals(2, $this->cart->countItems());
        $this->assertEquals(2.0, $this->cart->getItem('A')->getCartQuantity());
        $this->assertEquals(1.0, $this->cart->getItem('B')->getCartQuantity());
    }

    public function testSetsItems(): void
    {
        // Clear the cart to avoid interference from items added in setUp
        $this->cart->clear();

        // Set up the first item
        $item = $this->createMock(CartItemInterface::class);
        $item->method('getCartId')->willReturn('A');
        $item->method('getCartType')->willReturn('product');
        $item->method('getCartQuantity')->willReturn(3);
        $item->method('getUnitPrice')->willReturn(1);
        $item->method('getTaxRate')->willReturn(10);
        $item->expects($this->once())->method('setCartQuantity')->with(3);
        $item->expects($this->once())->method('setCartContext')->with($this->cart->getContext());

        // Set up the second item
        $item2 = $this->createMock(CartItemInterface::class);
        $item2->method('getCartId')->willReturn('B');
        $item2->method('getCartType')->willReturn('product');
        $item2->method('getCartQuantity')->willReturn(1);
        $item2->method('getUnitPrice')->willReturn(0.825);
        $item2->method('getTaxRate')->willReturn(20);
        $item2->expects($this->once())->method('setCartQuantity')->with(1);
        $item2->expects($this->once())->method('setCartContext')->with($this->cart->getContext());

        // Set the items in the cart
        $this->cart->setItems([$item, $item2]);

        // Assert that the total is as expected
        $this->assertTrue($this->cart->getTotal()->equals(Decimal::fromFloat(4.29)));
    }

    public function testChangesItemQuantity(): void
    {
        $this->item->expects($this->once())->method('setCartQuantity')->with(7);
        $this->cart->setItemQuantity('A', 7);
    }

    public function testRemovesItem(): void
    {
        $this->assertTrue($this->cart->hasItem('A'));
        $this->cart->removeItem('A');
        $this->assertFalse($this->cart->hasItem('A'));
    }

    public function testMergesItemsOfSameId(): void
    {
        $item3 = $this->createMock(CartItemInterface::class);
        $item3->method('getCartId')->willReturn('A');
        $item3->method('getCartType')->willReturn('product');
        $this->item->expects($this->once())->method('getCartQuantity');
        $this->item->expects($this->once())->method('setCartQuantity')->with(4.3);
        $this->cart->addItem($item3, 2.3);
    }

    public function testChecksEmptyStateCorrectly(): void
    {
        $this->assertFalse($this->cart->isEmpty());
        $this->cart->clear();
        $this->assertTrue($this->cart->isEmpty());
    }

    public function testGetsItemsByFilter(): void
    {
        $item3 = $this->createMock(CartItemInterface::class);
        $item3->method('getCartId')->willReturn('T');
        $item3->method('getCartType')->willReturn('test');
        $item3->method('getCartQuantity')->willReturn(1.0);
        $item3->method('getUnitPrice')->willReturn(1);
        $item3->method('getTaxRate')->willReturn(0);
        $item3->expects($this->once())->method('setCartQuantity')->with(1);
        $item3->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item3);

        $this->assertEquals(['T' => $item3], $this->cart->getItems('test'));
        $this->assertEquals(['A' => $this->item, 'B' => $this->item2], $this->cart->getItems('product'));
        $this->assertEquals(['A' => $this->item, 'B' => $this->item2], $this->cart->getItems('~test'));
        $this->assertEquals(['A' => $this->item], $this->cart->getItems(fn($item) => $item->getCartId() === 'A'));

        $this->assertTrue($this->cart->getTotal('product')->equals(Decimal::fromFloat(3.19)));
        $this->assertTrue($this->cart->getTotal('test')->equals(Decimal::fromInteger(1)));
        $this->assertTrue($this->cart->getTotal('product,nonexistent,test')->equals(Decimal::fromFloat(4.19)));
        $this->assertTrue($this->cart->getTotal('~test')->equals(Decimal::fromFloat(3.19)));
        $this->assertTrue($this->cart->getTotal(fn($item) => $item->getCartId() === 'A')->equals(Decimal::fromFloat(2.2)));

        $item4 = $this->createMock(WeightedCartItemInterface::class);
        $item4->method('getCartId')->willReturn('W');
        $item4->method('getCartType')->willReturn('weighted');
        $item4->method('getCartQuantity')->willReturn(3.0);
        $item4->method('getUnitPrice')->willReturn(1);
        $item4->method('getTaxRate')->willReturn(0.0);
        $item4->method('getWeight')->willReturn(0.5);
        $item4->expects($this->once())->method('setCartQuantity')->with(3);
        $item4->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item4, 3);

        $this->assertTrue($this->cart->getWeight()->equals(Decimal::fromFloat(1.5)));
        $this->assertTrue($this->cart->getWeight('weighted')->equals(Decimal::fromFloat(1.5)));
        $this->assertTrue($this->cart->getWeight('weighted,nonexistent,test')->equals(Decimal::fromFloat(1.5)));
        $this->assertTrue($this->cart->getWeight('product,nonexistent,test')->isZero());
    }

    public function testCountsTotalsForGrossPricesCorrectly(): void
    {
        $this->assertTrue($this->cart->getSubtotal()->equals(Decimal::fromFloat(2.82)));
        $this->assertTrue($this->cart->getTotal()->equals(Decimal::fromFloat(3.19)));

        $taxes = $this->cart->getTaxes();
        $this->assertTrue($taxes[10]->equals(Decimal::fromFloat(0.2)));
        $this->assertTrue($taxes[20]->equals(Decimal::fromFloat(0.17)));

        $taxBases = $this->cart->getTaxBases();
        $this->assertTrue($taxBases[10]->equals(Decimal::fromInteger(2)));
        $this->assertTrue($taxBases[20]->equals(Decimal::fromFloat(0.82)));

        $taxTotals = $this->cart->getTaxTotals();
        $this->assertTrue($taxTotals[10]->equals(Decimal::fromFloat(2.2)));
        $this->assertTrue($taxTotals[20]->equals(Decimal::fromFloat(0.99)));
    }

    public function testCountsTotalsForNetPricesCorrectly(): void
    {
        $this->cart->setPricesWithVat(false);

        $this->assertTrue($this->cart->getSubtotal()->equals(Decimal::fromFloat(2.83)));
        $this->assertTrue($this->cart->getTotal()->equals(Decimal::fromFloat(3.2)));

        $taxes = $this->cart->getTaxes();
        $this->assertTrue($taxes[10]->equals(Decimal::fromFloat(0.2)));
        $this->assertTrue($taxes[20]->equals(Decimal::fromFloat(0.17)));

        $taxBases = $this->cart->getTaxBases();
        $this->assertTrue($taxBases[10]->equals(Decimal::fromInteger(2)));
        $this->assertTrue($taxBases[20]->equals(Decimal::fromFloat(0.83)));

        $taxTotals = $this->cart->getTaxTotals();
        $this->assertTrue($taxTotals[10]->equals(Decimal::fromFloat(2.2)));
        $this->assertTrue($taxTotals[20]->equals(Decimal::fromInteger(1)));
    }

    public function testHandlesPromotions(): void
    {
        $promotion1 = $this->createMock(PromotionInterface::class);
        $promotion2 = $this->createMock(PromotionInterface::class);

        $promotion1->method('isEligible')->willReturn(true);
        $promotion2->method('isEligible')->willReturn(false);

        $promotion1->expects($this->once())->method('beforeApply')->with($this->cart);
        $promotion2->expects($this->once())->method('beforeApply')->with($this->cart);
        $promotion1->expects($this->once())->method('apply')->with($this->cart);
        $promotion1->expects($this->once())->method('afterApply')->with($this->cart);
        $promotion2->expects($this->once())->method('afterApply')->with($this->cart);

        $this->cart->setPromotions([$promotion1, $promotion2]);
        $this->cart->removeItem('A');
    }

    public function testRemovesBoundItem(): void
    {
        $item3 = $this->createMock(BoundCartItemInterface::class);
        $item3->method('getCartId')->willReturn('BOUND');
        $item3->method('getCartType')->willReturn('bound item');
        $item3->method('getCartQuantity')->willReturn(1.0);
        $item3->method('getUnitPrice')->willReturn(1);
        $item3->method('getTaxRate')->willReturn(0.0);
        $item3->method('getBoundItemCartId')->willReturn('A');
        $item3->method('updateCartQuantityAutomatically')->willReturn(false);
        $item3->expects($this->once())->method('setCartQuantity')->with(1);
        $item3->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item3);

        $item4 = $this->createMock(BoundCartItemInterface::class);
        $item4->method('getCartId')->willReturn('BOUND2');
        $item4->method('getCartType')->willReturn('bound item 2');
        $item4->method('getCartQuantity')->willReturn(1.0);
        $item4->method('getUnitPrice')->willReturn(1);
        $item4->method('getTaxRate')->willReturn(0.0);
        $item4->method('getBoundItemCartId')->willReturn('A');
        $item4->method('updateCartQuantityAutomatically')->willReturn(false);
        $item4->expects($this->once())->method('setCartQuantity')->with(1);
        $item4->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item4);

        $this->cart->removeItem('A');
        $this->assertFalse($this->cart->hasItem('BOUND'));
        $this->assertFalse($this->cart->hasItem('BOUND2'));
    }

    public function testUpdatesBoundItemQuantityAutomatically(): void
    {
        $item3 = $this->createMock(BoundCartItemInterface::class);
        $item3->method('getCartId')->willReturn('BOUND');
        $item3->method('getCartType')->willReturn('bound item');
        $item3->method('getCartQuantity')->willReturn(2.0);
        $item3->method('getUnitPrice')->willReturn(1);
        $item3->method('getTaxRate')->willReturn(0.0);
        $item3->method('getBoundItemCartId')->willReturn('A');
        $item3->method('updateCartQuantityAutomatically')->willReturn(true);
        $item3->expects($this->once())->method('setCartQuantity')->with(2);
        $item3->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item3);

        $item4 = $this->createMock(BoundCartItemInterface::class);
        $item4->method('getCartId')->willReturn('BOUND2');
        $item4->method('getCartType')->willReturn('bound item 2');
        $item4->method('getCartQuantity')->willReturn(1.0);
        $item4->method('getUnitPrice')->willReturn(1);
        $item4->method('getTaxRate')->willReturn(0.0);
        $item4->method('getBoundItemCartId')->willReturn('A');
        $item4->method('updateCartQuantityAutomatically')->willReturn(false);
        $item4->expects($this->once())->method('setCartQuantity')->with(1);
        $item4->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item4);

        $this->item->expects($this->once())->method('setCartQuantity')->with(7);
        $item3->expects($this->once())->method('setCartQuantity')->with(7);
        $this->cart->setItemQuantity('A', 7);
    }

    public function testRemovesMultipleBoundItem(): void
    {
        $item3 = $this->createMock(MultipleBoundCartItemInterface::class);
        $item3->method('getCartId')->willReturn('BOUND');
        $item3->method('getCartType')->willReturn('bound item');
        $item3->method('getCartQuantity')->willReturn(1.0);
        $item3->method('getUnitPrice')->willReturn(1);
        $item3->method('getTaxRate')->willReturn(0.0);
        $item3->method('getBoundItemCartIds')->willReturn(['A', 'B']);
        $item3->expects($this->once())->method('setCartQuantity')->with(1);
        $item3->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item3);

        $this->item->expects($this->once())->method('setCartQuantity')->with(7);
        $this->cart->setItemQuantity('A', 7);

        $this->cart->removeItem('B');
        $this->assertFalse($this->cart->hasItem('BOUND'));
    }

    public function testSortsItemsCorrectly(): void
    {
        $item3 = $this->createMock(CartItemInterface::class);
        $item3->method('getCartId')->willReturn('L');
        $item3->method('getCartType')->willReturn('last');
        $item3->method('getCartQuantity')->willReturn(1.0);
        $item3->method('getUnitPrice')->willReturn(1);
        $item3->method('getTaxRate')->willReturn(0.0);
        $item3->expects($this->once())->method('setCartQuantity')->with(1);
        $item3->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item3);

        $item4 = $this->createMock(CartItemInterface::class);
        $item4->method('getCartId')->willReturn('F');
        $item4->method('getCartType')->willReturn('first');
        $item4->method('getCartQuantity')->willReturn(3.0);
        $item4->method('getUnitPrice')->willReturn(1);
        $item4->method('getTaxRate')->willReturn(0.0);
        $item4->expects($this->once())->method('setCartQuantity')->with(3);
        $item4->expects($this->once())->method('setCartContext')->with($this->cart->getContext());
        $this->cart->addItem($item4, 3);

        $this->assertEquals(['A' => $this->item, 'B' => $this->item2, 'L' => $item3, 'F' => $item4], $this->cart->getItems());

        $this->cart->sortByType(['first', 'product', 'last']);
        $this->assertEquals(['F' => $item4, 'A' => $this->item, 'B' => $this->item2, 'L' => $item3], $this->cart->getItems());
    }

    public function testCanSetRounding(): void
    {
        $this->cart->setTotalRounding(fn(Decimal $total) => $total->round());
        $this->assertTrue($this->cart->getTotal()->equals(Decimal::fromFloat(3.0)));
        $this->assertTrue($this->cart->getRoundingAmount()->equals(Decimal::fromFloat(-0.19)));
    }
}