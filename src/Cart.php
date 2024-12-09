<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

use UnexpectedValueException;

class Cart implements CartInterface
{

    /**
     * @var array<string,CartItemCounter> $items
     */
    protected array $items = [];
    /**
     * @var array<string,PromotionInterface> $promotions
     */
    protected array $promotions = [];

    public function addItem(CartItemInterface $item, int $quantity = 1): void
    {
        $id = CartHelpers::getItemId($item);
        if (isset($this->items[$id])) {
            $this->items[$id]->quantity += $quantity;
            return;
        }
        $this->items[$id] = new CartItemCounter(item: $item, quantity: $quantity);
    }


    private function findItem(CartItemInterface $item): ?CartItemCounter
    {
        return $this->items[CartHelpers::getItemId($item)] ?? null;
    }

    public function getItemQuantity(CartItemInterface $item): int
    {
        $result = $this->findItem($item);
        if (null === $result) {
            return 0;

        }
        return $result->quantity;
    }


    public function hasItem(CartItemInterface $item): bool
    {
        return $this->findItem($item) !== null;
    }

    public function removeItem(CartItemInterface $item, int $qty = null): void
    {
        $id = CartHelpers::getItemId($item);
        if (!isset($this->items[$id])) {
            return;
        }
        if ($qty === null) {
            unset($this->items[$id]);
            return;
        }
        $this->items[$id]->quantity -= $qty;
        if ($this->items[$id]->quantity < 0) {
            unset($this->items[$id]);
        }
    }

    public function addPromotion(PromotionInterface $promotion): void
    {
        if (!$this->hasPromo($promotion)) {
            $this->promotions[CartHelpers::getItemId($promotion)] = $promotion;
        }
    }

    public function removePromotion(PromotionInterface $promotion): void
    {
        if (isset($this->promotions[CartHelpers::getItemId($promotion)])) {
            unset($this->promotions[CartHelpers::getItemId($promotion)]);
        }
    }

    /**
     * @return list<CartItemInterface>
     */
    public function getItems(): array
    {
        return array_values(array_map(static fn(CartItemCounter $item) => $item->item, ($this->items)));
    }

    /**
     * @return list<PromotionInterface>
     */
    public function getPromotions(): array
    {
        return array_values($this->promotions);
    }

    public function hasPromo(PromotionInterface $promotion): bool
    {
        return isset($this->promotions[CartHelpers::getItemId($promotion)]);
    }


    public function clearItems(): void
    {
        $this->items = [];
    }

    public function clearPromotions(): void
    {
        $this->promotions = [];
    }

    public function clear(): void
    {
        $this->clearItems();
        $this->clearPromotions();
    }

    /**
     * @param list<CartItemCounter> $a
     * @param list<CartItemCounter> $b
     * @return list<CartItemDifference>
     */
    private function itemsDiff(array $a, array $b): array
    {
        $keyedA = CartHelpers::makeKeyedItems($a);
        $keyedB = CartHelpers::makeKeyedItems($b);
        $items = [];

        foreach ($keyedA as $item) {
            $items[] = $item;
        }
        foreach ($keyedB as $item) {
            $items[] = $item;
        }
        $keyedACount = array_map(static function (CartItemCounter $item) {
            return $item->quantity;
        }, $keyedA);
        $keyedBCount = array_map(static function (CartItemCounter $item) {
            return $item->quantity;
        }, $keyedB);

        $diff = $keyedBCount;
        foreach ($keyedACount as $itemId => $count) {
            $diff[$itemId] -= $count;

        }
        $objDiff = [];
        foreach ($diff as $itemId => $count) {
            $foundItem = null;
            foreach ($items as $item) {
                if (CartHelpers::getItemId($item->getItem()) === $itemId) {
                    $foundItem = $item;
                }

            }
            if ($foundItem === null) {
                throw new UnexpectedValueException('Item not found');
            }
            if ($count === 0) {
                continue;
            }
            $objDiff[] = new CartItemDifference(item: $foundItem->item, difference: $count);
        }

        return $objDiff;
    }








    /**
     * @param list<PromotionInterface> $promotions
     * @return list<PromotionInterface>
     */
    private function excludePromotion(PromotionInterface $promotion, array $promotions): array
    {
        $excluded = [];
        foreach ($promotions as $promoItem) {
            if (CartHelpers::getItemId($promotion) !== CartHelpers::getItemId($promoItem)) {
                $excluded[] = $promoItem;
            }
        }
        return $excluded;
    }

    /**
     * @param list<PromotionInterface> $promotions
     * @param array<string,CartPromoImpact>  &$promoImpact
     * @return list<PromotionInterface>
     */
    private function performPromotionReduce(array $promotions, array &$promoImpact): array
    {
        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); $i++) {
            $promotion = $promotions[$i];

            $newPromotions = $promotion->reducePromotions(
                new ModifiedCartData(items: $this->convertToModified(array_values($this->items)), promotions: $promotions, cart: $this),
                $this->excludePromotion($promotion, $promotions)
            );

            $newPromotions[] = $promotion;


            $diff = CartHelpers::promoDiff($promotions, $newPromotions);
            if (count($diff) === 0) {
                continue;
            }
            foreach ($diff as $item) {
                if ($item['diff'] === 0) {
                    continue 2;
                }
            }
            $promoImpact[CartHelpers::getItemId($promotion)] = new CartPromoImpact(
                promotion: $promotion, cartItemsDiff: [], promotionsDiff: $diff
            );
            $i = 0;
            $promotions = $newPromotions;
        }
        return $promotions;
    }

    public function performTotals(): CartTotals
    {
        /**
         * @var list<CartItemCounter> $items
         */
        $items = array_map(static fn(CartItemCounter $item) => clone $item, $this->items);
        /**
         * @var list<PromotionInterface> $promotions
         */
        $promotions = array_values(array_filter($this->promotions, fn(PromotionInterface $p) => $p->isEligible($this)));
        /**
         * @var array<string,CartPromoImpact> $promoImpact
         */
        $promoImpact = [];

        $promotions =  $this->performPromotionReduce($promotions, $promoImpact);

        /**
         * @var list<CartItemPromoImpact> $promotionItemsImpact
         */
        $promotionItemsImpact = [];
        $items = $this->performItemReduce($items, $promotions, $promoImpact);

        /**
         * @var array<string,CartItemSubTotal> $itemSubTotals
         */
        $itemSubTotals = [];
        $context = new PromoCalculationsContext();

        $this->performItemPriceReduce(promotions: $promotions, items: $items, itemPromoImpacts: $promotionItemsImpact, itemSubTotals: $itemSubTotals, context: $context);

        $this->performAfterPriceReduce(
            itemSubTotals: $itemSubTotals, promotions: $promotions, promotionItemsImpact: $promotionItemsImpact, items: $items, context: $context
        );


        $keyed = CartHelpers::makeKeyedItems($items);
        $keyedPromo = CartHelpers::makeKeyedPromo($promotions);
        return new CartTotals(
            cart: $this,
            items: $keyed,
            itemSubTotals: $itemSubTotals,
            promotionItemsImpact: $promotionItemsImpact,
            promotionsImpact: ($promoImpact),
            promotions: $keyedPromo
        );

    }

    /**
     * @param list<PromotionInterface> $promotions
     * @param list<CartItemCounter> $items
     * @param list<CartItemPromoImpact> $promotionItemsImpact
     * @param array<string,CartItemSubTotal> $itemSubTotals
     * @param PromoCalculationsContext $context
     * @return void
     */
    private function performAfterPriceReduce(array &$itemSubTotals, array $promotions, array &$promotionItemsImpact, array $items, PromoCalculationsContext $context): void
    {
        foreach ($promotions as $promotion) {
            $totalsContainer = [];
            foreach ($itemSubTotals as $subTotalItem) {
                $totalsContainer[] = new CartItemSubTotalReducer(
                    item: $subTotalItem->item, quantity: $subTotalItem->quantity, subTotal: $subTotalItem->subTotalAfterPromo,
                );
            }

            $promotion->reduceItemsSubTotal($totalsContainer, $context, new ModifiedCartData(items: $this->convertToModified($items), promotions: $promotions, cart: $this));
            foreach ($totalsContainer as $subTotalCounter) {
                $itemId = CartHelpers::getItemId($subTotalCounter->item);
                $diff = $subTotalCounter->subTotal->sub($itemSubTotals[$itemId]->subTotalAfterPromo,5);
                if (!$diff->isZero()) {
                    $promotionItemsImpact[] = new CartItemPromoImpact(
                        item: $subTotalCounter->item,
                        promotion: $promotion,
                        priceImpact: $diff
                    );
                    $itemSubTotals[$itemId] = new CartItemSubTotal(
                        item: $subTotalCounter->item,
                        quantity: $subTotalCounter->quantity,
                        subTotalBeforePromo: $itemSubTotals[$itemId]->subTotalBeforePromo,
                        subTotalAfterPromo: $subTotalCounter->subTotal
                    );
                }

            }
        }
    }


    /**
     * @param list<CartItemCounter> $items
     * @return list<ModifiedCartItemData>
     */
    private function convertToModified(array $items): array
    {
        return array_map(
            static fn(CartItemCounter $item) => new ModifiedCartItemData(
                item: $item->item,
                quantity: $item->quantity
            ), $items
        );
    }

    /**
     * @param list<PromotionInterface> $promotions
     * @param list<CartItemCounter> $items
     * @param list<CartItemPromoImpact> $itemPromoImpacts
     * @param array<string,CartItemSubTotal> $itemSubTotals
     */
    private function performItemPriceReduce(array $promotions, array $items, array &$itemPromoImpacts, array &$itemSubTotals, PromoCalculationsContext $context): void

    {
        foreach ($items as $counter) {
            $item = $counter->getItem();

            $itemId = CartHelpers::getItemId($item);
            $quantity = $this->getItemQuantity($item);
            $before = Decimal::fromFloat($item->getUnitPrice() * $quantity,4);

            $subTotal = $before;
            foreach ($promotions as $promotion) {
                $staleCart = new ModifiedCartData(items: $this->convertToModified($items), promotions: $promotions, cart: $this);
                $subTotalBeforePromoItem = $subTotal;
                $subTotal = $promotion
                    ->reduceItemSubtotal(
                        cart: $staleCart,
                        item: $item,
                        subTotal: $subTotal,
                        context: $context
                    );
                if ($subTotal->isNegative()) {
                    $subTotal = Decimal::fromInteger(0);
                }

                $impact = $subTotal->sub($subTotalBeforePromoItem,5);
                if (!$impact->isZero()) {
                    $itemPromoImpacts[] = new CartItemPromoImpact(
                        item: $item,
                        promotion: $promotion,
                        priceImpact: $impact
                    );
                }
            }
            $itemSubTotals[$itemId] = new CartItemSubTotal(
                item: $item,
                quantity: $quantity,
                subTotalBeforePromo: $before,
                subTotalAfterPromo: $subTotal
            );
        }


    }


    /**
     * @param list<CartItemCounter> $items
     *
     * @param list<PromotionInterface> $promotions
     * @param array<string,CartPromoImpact> $promoImpact
     * @return list<CartItemCounter>
     */
    private function performItemReduce(array $items, array $promotions, array &$promoImpact): array
    {
        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); $i++) {
            $promotion = $promotions[$i];
            $modifiedCart = new ModifiedCartData(items: $this->convertToModified($items), promotions: $promotions, cart: $this);
            $newItems = $promotion->reduceItems($modifiedCart, $items);
            $diff = $this->itemsDiff($items, $newItems);
            $items = $newItems;
            $itemId = CartHelpers::getItemId($promotion);

            if (count($diff) !== 0) {
                $i = 0;
                $promoImpact[CartHelpers::getItemId($promotion)] = new CartPromoImpact(
                    promotion: $promotion,
                    cartItemsDiff: $diff,
                    promotionsDiff: isset($promoImpact[$itemId]) ? $promoImpact[$itemId]->promotionsDiff : []
                );
            }
        }
        return $items;
    }

}