<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart\Tests;

use PHPUnit\Framework\TestCase;
use RevoTale\ShoppingCart\Cart;
use RevoTale\ShoppingCart\CartHelpers;
use RevoTale\ShoppingCart\CartInterface;
use RevoTale\ShoppingCart\CartItemCounter;
use RevoTale\ShoppingCart\CartItemInterface;
use RevoTale\ShoppingCart\CartItemPromoImpact;
use RevoTale\ShoppingCart\CartItemSubTotal;
use RevoTale\ShoppingCart\CartItemSubTotalReducer;
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


        $cart->removeItem($this->item, 10);
        $totals = $cart->performTotals();
        self::assertEquals(null, $totals->getItemQuantity($this->item));
        $cart->addItem($this->item);
        $totals = $cart->performTotals();

        self::assertEquals(1, $totals->getItemQuantity($this->item));
        $cart->removeItem($this->item, 1);
        $totals = $cart->performTotals();

        self::assertEquals(null, $totals->getItemQuantity($this->item));
        $cart->removeItem($this->item);
        $totals = $cart->performTotals();

        self::assertEquals(null, $totals->getItemQuantity($this->item));

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

        self::assertEquals([-40, -120, -12], array_map(static fn(CartItemPromoImpact $impact): int => $impact->priceImpact->asInteger(), $totals->getPromotionItemsImpact()));
        self::assertEquals([[400, 360], [240, 108]], array_map(static fn(CartItemSubTotal $subTotal): array => [$subTotal->subTotalBeforePromo->asInteger(), $subTotal->subTotalAfterPromo->asInteger()], $totals->getItemSubTotals()));
        self::assertEquals((640 - 120) * 0.9, $totals->getTotal()->asInteger());

        self::assertEquals(['promo_10_percent', 'promo_free_product_item_2', 'promo_10_percent'], array_map(static fn(CartItemPromoImpact $impact): string => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
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

        self::assertEquals(['promo_10_percent', 'promo_free_product_item_2', 'promo_10_percent', 'fixed_discount_1', 'fixed_discount_1'], array_map(static fn(CartItemPromoImpact $impact): string => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
        self::assertEquals([-40.0, -120.0, -12.0, -153.85, -46.15], array_map(static fn(CartItemPromoImpact $impact): float => $impact->priceImpact->round(2)->asFloat(), $totals->getPromotionItemsImpact()));
        self::assertEquals([[400.0, 206.15], [240.0, 61.85]], array_map(static fn(CartItemSubTotal $subTotal): array => [$subTotal->subTotalBeforePromo->round(2)->asFloat(), $subTotal->subTotalAfterPromo->round(2)->asFloat()], $totals->getItemSubTotals()));
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
                return array_values(array_filter($promotions, static fn(PromotionInterface $promotion): bool => $promotion->getCartId() !== 'promo_free_product_item_2'));
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
        $keys = CartHelpers::makeKeyedItems([new CartItemCounter($this->item, quantity: 2), new CartItemCounter($this->item, quantity: 5), new CartItemCounter($this->item2, 2)]);
        self::assertCount(2, $keys);
        self::assertEquals(7, $keys['item_1________product']->quantity);
        self::assertEquals(2, $keys['item_2________product']->quantity);

    }

    public function testTotals(): void
    {
        $cart = $this->cart;
        $item = $this->item;
        $cart->addItem($item, 2);
        $fixedCartPromo = new class extends CartFixedSumDiscount {
            public function isEligible(CartInterface $cart): bool
            {
                return true;
            }

            public function getCartId(): string
            {
                return 'negative_promo';
            }

            public function getCartType(): string
            {
                return 'discount';
            }

            public function getDiscountAmount(): float
            {
                return 1000000;
            }
        };
        $cart->addPromotion($fixedCartPromo);
        $totals = $cart->performTotals();
        self::assertEquals(0, $totals->getTotal()->asInteger());
        self::assertEquals(0, $totals
            ->getItemSubTotals()[0]
            ->subTotalAfterPromo->asInteger());
        self::assertEquals(-400, $totals
            ->getPromotionItemsImpact()[0]
            ->priceImpact->asInteger());


        $cart->removePromotion($fixedCartPromo);
        $cart->addPromotion(new class implements PromotionInterface
        {

            public function isEligible(CartInterface $cart): bool
            {
               return true;
            }

            public function reduceItemSubtotal(ModifiedCartData $cart, CartItemInterface $item, Decimal $subTotal, PromoCalculationsContext $context): Decimal
            {
               return  $subTotal;
            }

            public function reduceItems(ModifiedCartData $cart, array $itemCounters): array
            {
                return $itemCounters;
            }

            public function reducePromotions(ModifiedCartData $cart, array $promotions): array
            {
               return $promotions;
            }

            public function getCartId(): string
            {
               return '1';
            }

            public function getCartType(): string
            {
             return 'ss';
            }

            /**
             * @param list<CartItemSubTotalReducer> $items
             */
            public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context, ModifiedCartData $data): void
            {
             $items[0]->setSubTotal(Decimal::fromInteger(-10000000));
            }
        });
        $totals = $cart->performTotals();
        self::assertEquals(0, $totals->getTotal()->asInteger());
        self::assertEquals(0, $totals
            ->getItemSubTotals()[0]
            ->subTotalAfterPromo->asInteger());
        self::assertEquals(-400, $totals
            ->getPromotionItemsImpact()[0]
            ->priceImpact->asInteger());
    }

    public function testQtyReduce(): void
    {
        $cart = new Cart();
        $cart->addItem($this->item, 2);
        $cart->removeItem($this->item, 2);

        $totals = $cart->performTotals();
        self::assertCount(0, $totals->getItems());

        $cart->addItem($this->item, 2);
        $promoZeroFirst = new class implements PromotionInterface {

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
                if ($itemCounters !== []) {
                    $itemCounters[0]->quantity = 0;
                }

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
        };
        $cart->addPromotion($promoZeroFirst);
        self::assertCount(0, $cart->performTotals()->getItems());
        self::assertCount(1, $cart->performTotals()->getPromotions());

        $cart->addPromotion(new class($this->item) implements PromotionInterface {
            public function __construct(private readonly CartItemInterface $item)
            {
            }

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
                return [...$itemCounters, new CartItemCounter($this->item, 2), new CartItemCounter($this->item, 5)];
            }

            public function reducePromotions(ModifiedCartData $cart, array $promotions): array
            {
                return $promotions;
            }

            public function getCartId(): string
            {
                return 's2';
            }

            public function getCartType(): string
            {
                return 'd';
            }

            public function reduceItemsSubTotal(array $items, PromoCalculationsContext $context, ModifiedCartData $data): void
            {

            }
        });
        $totals = $cart->performTotals();
        self::assertCount(2, $totals->getPromotions());
        self::assertCount(1, $totals->getItems());
        self::assertEquals(7, $totals->getItemQuantity($totals->getItems()[0]));


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
        self::assertEquals([-120, -48], array_map(static fn(CartItemPromoImpact $impact): int => $impact->priceImpact->asInteger(), $totals->getPromotionItemsImpact()));
        self::assertEquals(['promo_20_percent', 'promo_10_percent'], array_map(static fn(CartItemPromoImpact $impact): string => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
        self::assertEquals([], $totals->getNotEligible());

        self::assertEquals(600 * 0.8 * 0.9, $totals->getTotal()->asInteger());

        $promo2->eligible = false;
        $totals = $this->cart->performTotals();
        self::assertEquals([-60], array_map(static fn(CartItemPromoImpact $impact): int => $impact->priceImpact->asInteger(), $totals->getPromotionItemsImpact()));
        self::assertEquals(['promo_10_percent'], array_map(static fn(CartItemPromoImpact $impact): string => $impact->promotion->getCartId(), $totals->getPromotionItemsImpact()));
        self::assertEquals(600 * 0.9, $totals->getTotal()->asInteger());
        self::assertFalse($totals->isPromotionDiff());
        self::assertFalse($totals->isItemsDiff());
        self::assertEquals([$promo2], $totals->getNotEligible());

    }
}