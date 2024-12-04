<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart\Tests;

use PHPUnit\Framework\TestCase;
use RevoTale\ShoppingCart\Cart;
use RevoTale\ShoppingCart\CartContext;
use RevoTale\ShoppingCart\CartInterface;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\Decimal;
use RevoTale\ShoppingCart\ModifiedCartData;
use RevoTale\ShoppingCart\PromotionInterface;
use RevoTale\ShoppingCart\PromotionTemplates\CartPercentageDiscount;

final class CartTest extends TestCase
{
    private Cart $cart;
    private CartItemInterface $item;
    private CartItemInterface $item1Clone;
    private CartItemInterface $item2;

    private CartPercentageDiscount $promotion;
    private PromotionInterface $promotionFreeProduct;


    protected function setUp(): void
    {
        parent::setUp();
        $this->cart = new Cart();
        $this->item2 = new class implements CartItemInterface {

            public function getCartId(): string
            {
                return 'item_2';
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
                return 120;
            }
        };
        $this->promotionFreeProduct = new class implements PromotionInterface {
            public function getCartId(): string
            {
                return 'promo_free_product_item_2';
            }

            public function getCartType(): string
            {
                return 'discount';
            }

            public function isEligible(CartInterface $cart): bool
            {
                return true;
            }

            public function reduceItemSubtotal(ModifiedCartData $cart, CartItemInterface $item, Decimal $subTotal): Decimal
            {
                if ($item->getCartId() === 'item_2') {
                    return $subTotal->sub(Decimal::fromInteger($item->getUnitPrice()));
                }
                return $subTotal;
            }

            public function reduceItems(ModifiedCartData $cart, array $itemCounters): array
            {
                return $itemCounters;
            }

            public function reducePromotions(ModifiedCartData $cart, array $promotions): array
            {
                return $promotions;
            }
        };

        $this->promotion = new class  extends CartPercentageDiscount  {
            public function getCartId(): string
            {
                return 'promo_10_percent';
            }

            public function getCartType(): string
            {
                return 'discount';
            }

            public function isEligible(CartInterface $cart): bool
            {
                return true;
            }

           public function getDiscountMultiplier(): float
           {
               return 0.9;
           }
        };
        $this->item = new class implements CartItemInterface {

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
        $this->item1Clone = new class implements CartItemInterface {

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
    }

    public function testBasis(): void
    {
        $cart = $this->cart;
        $item = $this->item;
        $cart->addItem($item);
        self::assertEquals(200, $cart->performTotals()->getTotal()->asInteger());
        $cart->addItem($item, 2);
        self::assertEquals(600, $cart->performTotals()->getTotal()->asInteger());
        $item1Clone = $this->item1Clone;
        $cart->addItem($item1Clone);
        self::assertEquals(800, $cart->performTotals()->getTotal()->asInteger());
        $cart->removeItem($item1Clone, 2);
        self::assertEquals(400, $cart->performTotals()->getTotal()->asInteger());
        $item2 = $this->item2;
        $cart->addItem($item1Clone, 5);
        self::assertEquals(1400, $cart->performTotals()->getTotal()->asInteger());
        $cart->addItem($item2, 2);
        self::assertEquals(1640, $cart->performTotals()->getTotal()->asInteger());

        $cart->removeItem($item1Clone, 5);
        self::assertEquals(640, $cart->performTotals()->getTotal()->asInteger());
        $cart->removeItem($item1Clone);
        self::assertEquals(240, $cart->performTotals()->getTotal()->asInteger());


    }

    public function testPromo(): void
    {
        $cart = $this->cart;
        $item = $this->item;
        $cart->addItem($item, 2);
        self::assertEquals(400, $cart->performTotals()->getTotal()->asInteger());
        $item2 = $this->item2;
        $cart->addItem($item2, 2);
        self::assertEquals(640, $cart->performTotals()->getTotal()->asInteger());
        $cart->addPromotion($this->promotion);
        self::assertEquals(640 - 64, $cart->performTotals()->getTotal()->asInteger());


    }
    public function testPromoFreeProduct(): void
    {
        $cart = $this->cart;
        $item = $this->item;
        $cart->addItem($item, 2);
        self::assertEquals(400, $cart->performTotals()->getTotal()->asInteger());
        $item2 = $this->item2;
        $cart->addItem($item2, 2);
        $cart->addPromotion($this->promotionFreeProduct);
        self::assertEquals((640 - 120), $cart->performTotals()->getTotal()->asInteger());
        $cart->addPromotion($this->promotion);
        self::assertEquals((640 - 120)*0.9, $cart->performTotals()->getTotal()->asInteger());
    }
}