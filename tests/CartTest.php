<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart\Tests;

use PHPUnit\Framework\TestCase;
use RevoTale\ShoppingCart\Cart;
use RevoTale\ShoppingCart\CartHelpers;
use RevoTale\ShoppingCart\CartInterface;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\CartItemPromoImpact;
use RevoTale\ShoppingCart\CartItemSubTotal;
use RevoTale\ShoppingCart\CartPromoImpact;
use RevoTale\ShoppingCart\PromoCalculationsContext;
use RevoTale\ShoppingCart\Decimal;
use RevoTale\ShoppingCart\ModifiedCartData;
use RevoTale\ShoppingCart\PromotionInterface;
use RevoTale\ShoppingCart\PromotionTemplates\CartFixedSumDiscount;
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

            public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context, ModifiedCartData $data): void
            {

            }

            public function reduceItemSubtotal(ModifiedCartData $cart, CartItemInterface $item, Decimal $subTotal, PromoCalculationsContext $context): Decimal
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

        $this->promotion = new class extends CartPercentageDiscount {
            public function getCartId(): string
            {
                return 'promo_10_percent';
            }

            public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context, ModifiedCartData $data): void
            {

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

    public function testPromoHasNiceOrdering(): void
    {

        $cart = $this->cart;
        $item = $this->item;
        $cart->addItem($item, 2);

        $cart->addPromotion($this->promotionFreeProduct);
        $cart->addItem($item, 2);
        $cart->addPromotion($this->promotion);
        self::assertEquals([$this->promotionFreeProduct, $this->promotion], $cart->getPromotions());
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
        $totals = $cart->performTotals();

        self::assertEquals([-40, -120, -12], array_map(static fn(CartItemPromoImpact $impact) => $impact->priceImpact->asInteger(), $totals->getPromotionItemsImpact()));
        self::assertEquals([[400, 360], [240, 108]], array_map(static fn(CartItemSubTotal $subTotal) => [$subTotal->subTotalBeforePromo->asInteger(), $subTotal->subTotalAfterPromo->asInteger()], $totals->getItemSubTotals()));
        self::assertEquals((640 - 120) * 0.9, $totals->getTotal()->asInteger());

        self::assertEquals(['promo_10_percent', 'promo_free_product_item_2', 'promo_10_percent'], array_map(static fn(CartItemPromoImpact $impact) => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
        $fixedCartPromo = new class extends CartFixedSumDiscount {
            public function isEligible(CartInterface $cart): bool
            {
                return true;
            }

            public function getCartId(): string
            {
                return 'fixed_discount_1';
            }

            public function getCartType(): string
            {
                return 'discount';
            }

            public function getDiscountAmount(): float
            {
                return 200;
            }
        };
        $cart->addPromotion($fixedCartPromo);
        $totals = $cart->performTotals();

        self::assertEquals((640 - 120) * 0.9 - 200, $totals->getTotal()->asInteger());

        self::assertEquals(['promo_10_percent', 'promo_free_product_item_2', 'promo_10_percent', 'fixed_discount_1', 'fixed_discount_1'], array_map(static fn(CartItemPromoImpact $impact) => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
        self::assertEquals([-40.0, -120.0, -12.0, -153.85, -46.15], array_map(static fn(CartItemPromoImpact $impact) => $impact->priceImpact->round(2)->asFloat(), $totals->getPromotionItemsImpact()));
        self::assertEquals([[400.0, 206.15], [240.0, 61.85]], array_map(static fn(CartItemSubTotal $subTotal) => [$subTotal->subTotalBeforePromo->round(2)->asFloat(), $subTotal->subTotalAfterPromo->round(2)->asFloat()], $totals->getItemSubTotals()));
        $cart->removePromotion($this->promotionFreeProduct);
        $cart->removePromotion($fixedCartPromo);
        $cart->addPromotion(new class extends CartFixedSumDiscount {
            public function isEligible(CartInterface $cart): bool
            {
                return true;
            }

            public function getCartId(): string
            {
                return 'fixed_discount_1';
            }

            public function getCartType(): string
            {
                return 'discount';
            }

            public function getDiscountAmount(): float
            {
                return 200;
            }

            public function reducePromotions(ModifiedCartData $cart, array $promotions): array
            {
                $promotions = parent::reducePromotions($cart, $promotions);
                return array_values(array_filter($promotions, static fn(PromotionInterface $promotion) => $promotion->getCartId() !== 'promo_free_product_item_2'));
            }
        });
        $cart->addPromotion($this->promotionFreeProduct);
        $totals = $cart->performTotals();
        self::assertCount(1, $totals->getPromotionsImpact()[0]->getRemovedPromos());

        self::assertEquals((640) * 0.9 - 200, $totals->getTotal()->asInteger());
        self::assertTrue($totals->isPromotionDiff());
        self::assertNotTrue($totals->isItemsDiff());

    }

    public function testCartUtils(): void
    {
        $diff = CartHelpers::promoDiff([$this->promotion, $this->promotionFreeProduct], [$this->promotionFreeProduct]);
        self::assertEquals([[
            'diff' => -1,
            'item' => $this->promotion
        ]], $diff);
        $diff = CartHelpers::promoDiff([$this->promotionFreeProduct], [$this->promotion, $this->promotionFreeProduct]);
        self::assertEquals([[
            'diff' => 1,
            'item' => $this->promotion
        ]], $diff);
    }

    public function testQtyReduce():void
    {
        $cart = $this->cart;
        $cart->addItem($this->item,2);
        $cart->removeItem($this->item,2);
        self::assertCount(0,$cart->performTotals()->getItems());

        $cart->addItem($this->item,2);
        $cart->addPromotion(new class implements PromotionInterface
        {

            public function isEligible(CartInterface $cart): bool
            {
               return true;
            }

            public function reduceItemSubtotal(ModifiedCartData $cart, CartItemInterface $item, Decimal $subTotal, PromoCalculationsContext $context): Decimal
            {
                return $subTotal;
            }

            public function reduceItems(ModifiedCartData $cart, array $itemCounters): array
            {
                $itemCounters[0]->quantity = 0;
                return $itemCounters;
            }

            public function reducePromotions(ModifiedCartData $cart, array $promotions): array
            {
                return $promotions;
            }

            public function getCartId(): string
            {
                return 's';
            }

            public function getCartType(): string
            {
               return 'd';
            }

            public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context, ModifiedCartData $data): void
            {

            }
        });
        self::assertCount(0,$cart->performTotals()->getItems());
    }

    public function testEligible(): void
    {
        $promo1 = new class extends CartPercentageDiscount {
            public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context, ModifiedCartData $data): void
            {

            }

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
        $promo2 = new class extends CartPercentageDiscount {
            public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context, ModifiedCartData $data): void
            {

            }

            public function __construct(public bool $eligible = true)
            {
            }

            public function getCartId(): string
            {
                return 'promo_20_percent';
            }

            public function getCartType(): string
            {
                return 'discount';
            }

            public function isEligible(CartInterface $cart): bool
            {
                return $this->eligible;
            }

            public function getDiscountMultiplier(): float
            {
                return 0.8;
            }
        };
        $this->cart->addItem($this->item, 3);
        $this->cart->addPromotion($promo2);
        $this->cart->addPromotion($promo1);
        $totals = $this->cart->performTotals();
        self::assertCount(2, $totals->getPromotionItemsImpact());
        self::assertEquals([-120, -48], array_map(static fn(CartItemPromoImpact $impact) => $impact->priceImpact->asInteger(), $totals->getPromotionItemsImpact()));
        self::assertEquals(['promo_20_percent', 'promo_10_percent'], array_map(static fn(CartItemPromoImpact $impact) => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
        self::assertEquals([],$totals->getNotEligible());

        self::assertEquals(600 * 0.8 * 0.9, $totals->getTotal()->asInteger());

        $promo2->eligible = false;
        $totals = $this->cart->performTotals();
        self::assertEquals([-60], array_map(static fn(CartItemPromoImpact $impact) => $impact->priceImpact->asInteger(), $totals->getPromotionItemsImpact()));
        self::assertEquals(['promo_10_percent'], array_map(static fn(CartItemPromoImpact $impact) => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
        self::assertEquals(600 * 0.9, $totals->getTotal()->asInteger());
        self::assertFalse($totals->isPromotionDiff());
        self::assertFalse($totals->isItemsDiff());
        self::assertEquals([$promo2],$totals->getNotEligible());

    }
}