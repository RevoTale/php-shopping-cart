<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart\Tests;

use PHPUnit\Framework\TestCase;
use RevoTale\ShoppingCart\BoundCartItemInterface;
use RevoTale\ShoppingCart\CartLgacy;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\Decimal;
use RevoTale\ShoppingCart\MultipleBoundCartItemInterface;
use RevoTale\ShoppingCart\PromotionInterface;
use RevoTale\ShoppingCart\WeightedCartItemInterface;

class CartTest extends TestCase
{
    public function testAddsItems(): void
    {
        $cart = new CartLgacy();

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
        $cart = new CartLgacy(roundingDecimals: 2);

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
        $cart = new CartLgacy();

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
        $cart = new CartLgacy();

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
        $cart = new CartLgacy();

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
        $cart = new CartLgacy();

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
        $cart = new CartLgacy(roundingDecimals: 2);

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
        $cart = new CartLgacy(roundingDecimals: 2);

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
        $cart = new CartLgacy();

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
        $cart = new CartLgacy(roundingDecimals: 2);

        // Add items
        $itemA = $this->createMock(CartItemInterface::class);
        $itemA->method('getCartId')->willReturn('A');
        $itemA->method('getCartType')->willReturn('product');
        $itemA->method('getUnitPrice')->willReturn(1.0);
        $itemA->method('getTaxRate')->willReturn(10.0);
        $itemA->method('getCartQuantity')->willReturn(2.0);
        $itemA->expects($this->atLeastOnce())->method('setCartQuantity')->with(2.0);
        $itemA->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemA,2.0);

        $itemB = $this->createMock(CartItemInterface::class);
        $itemB->method('getCartId')->willReturn('B');
        $itemB->method('getCartType')->willReturn('product');
        $itemB->method('getUnitPrice')->willReturn(0.825);
        $itemB->method('getTaxRate')->willReturn(20.0);
        $itemB->method('getCartQuantity')->willReturn(1.0);
        $itemB->expects($this->atLeastOnce())->method('setCartQuantity')->with(1.0);
        $itemB->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($itemB);

        $cart->setTotalRounding(fn(Decimal $total) => $total->round());

        $this->assertTrue($cart->getTotal()->equals(Decimal::fromFloat(3.0)));
        $this->assertTrue($cart->getRoundingAmount()->equals(Decimal::fromFloat(-0.19)));
    }

    public function testPercentCouponPromotion(): void
    {
        $cart = new CartLgacy();

        // Add items to the cart
        $item1 = $this->createMock(CartItemInterface::class);
        $item1->method('getCartId')->willReturn('A');
        $item1->method('getCartType')->willReturn('product');
        $item1->method('getCartQuantity')->willReturn(2.0);
        $item1->method('getUnitPrice')->willReturn(50.0); // $50 per item
        $item1->method('getTaxRate')->willReturn(10.0);
        $item1->expects($this->atLeastOnce())->method('setCartQuantity')->with(2.0);
        $item1->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($item1,2.0);

        // Create a promotion that applies a 20% discount
        $percentPromotion = $this->createMock(PromotionInterface::class);
        $percentPromotion->method('isEligible')->willReturn(true);
        $percentPromotion->method('beforeApply')->willReturnCallback(function ($cart) {
            // No action needed before apply
        });
        $percentPromotion->method('apply')->willReturnCallback(function (CartLgacy $cart) {
            // Apply a 20% discount by adding a discount item
            $discountAmount = $cart->getSubtotal()->mul(Decimal::fromFloat(0.20));
            $discountItem = $this->createMock(CartItemInterface::class);
            $discountItem->method('getCartId')->willReturn('DISCOUNT');
            $discountItem->method('getCartType')->willReturn('discount');
            $discountItem->method('getCartQuantity')->willReturn(1.0);
            $discountItem->method('getUnitPrice')->willReturn($discountAmount->additiveInverse()->asFloat());
            $discountItem->method('getTaxRate')->willReturn(0.0);
            $discountItem->expects($this->atLeastOnce())->method('setCartQuantity')->with(1.0);
            $discountItem->expects($this->atLeastOnce())->method('setCartContext')->with($cart->getContext());
            $cart->addItem($discountItem);
        });
        $percentPromotion->method('afterApply')->willReturnCallback(function ($cart) {
            // No action needed after apply
        });

        $cart->setPromotions([$percentPromotion]);


        // Expected calculations:
        // Subtotal: $50 x 2 = $100
        // Discount: $100 x 20% = $20
        // New Subtotal: $100 - $20 = $80
        // Tax: $80 x 10% = $8
        // Total: $80 + $8 = $88

        $subtotal = $cart->getSubtotal(); // Should be $80 after discount
        $tax = array_values($cart->getTaxes())[0];           // Should be $8
        $total = $cart->getTotal();       // Should be $88
        $this->assertTrue($subtotal->equals(Decimal::fromFloat(80.0)));
        $this->assertTrue($tax->equals(Decimal::fromFloat(8.0)));
        $this->assertTrue($total->equals(Decimal::fromFloat(88.0)));
    }

    public function testFixedPriceCouponPromotion(): void
    {
        $cart = new CartLgacy();

        // Add items to the cart
        $item1 = $this->createMock(CartItemInterface::class);
        $item1->method('getCartId')->willReturn('B');
        $item1->method('getCartType')->willReturn('product');
        $item1->method('getCartQuantity')->willReturn(1.0);
        $item1->method('getUnitPrice')->willReturn(120.0); // $120 per item
        $item1->method('getTaxRate')->willReturn(15.0);
        $item1->expects($this->once())->method('setCartQuantity')->with(1.0);
        $item1->expects($this->once())->method('setCartContext')->with($cart->getContext());
        $cart->addItem($item1);

        // Create a promotion that applies a fixed discount of $30
        $fixedPromotion = $this->createMock(PromotionInterface::class);
        $fixedPromotion->method('isEligible')->willReturn(true);
        $fixedPromotion->method('beforeApply')->willReturnCallback(function ($cart) {
            // No action needed before apply
        });
        $fixedPromotion->method('apply')->willReturnCallback(function ($cart) {
            $discountAmount = Decimal::fromFloat(30.0);
            $discountItem = $this->createMock(CartItemInterface::class);
            $discountItem->method('getCartId')->willReturn('DISCOUNT');
            $discountItem->method('getCartType')->willReturn('discount');
            $discountItem->method('getCartQuantity')->willReturn(1.0);
            $discountItem->method('getUnitPrice')->willReturn($discountAmount->additiveInverse()->asFloat());
            $discountItem->method('getTaxRate')->willReturn(0.0);
            $discountItem->expects($this->once())->method('setCartQuantity')->with(1.0);
            $discountItem->expects($this->once())->method('setCartContext')->with($cart->getContext());
            $cart->addItem($discountItem);
        });
        $fixedPromotion->method('afterApply')->willReturnCallback(function ($cart) {
            // No action needed after apply
        });

        $cart->setPromotions([$fixedPromotion]);

        // Recalculate totals

        // Expected calculations:
        // Subtotal: $120
        // Discount: $30
        // New Subtotal: $120 - $30 = $90
        // Tax: $90 x 15% = $13.50
        // Total: $90 + $13.50 = $103.50

        $subtotal = $cart->getSubtotal(); // Should be $90 after discount
        $tax = $cart->getTaxes()[0];           // Should be $13.50
        $total = $cart->getTotal();       // Should be $103.50

        $this->assertTrue($subtotal->equals(Decimal::fromFloat(90.0)));
        $this->assertTrue($tax->equals(Decimal::fromFloat(13.5)));
        $this->assertTrue($total->equals(Decimal::fromFloat(103.5)));
    }
}