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
        $id = CartHelpers::getItemKey($item);
        if (isset($this->items[$id])) {
            $this->items[$id]->quantity += $quantity;
            return;
        }

        $this->items[$id] = new CartItemCounter(item: $item, quantity: $quantity);
    }


    private function findItem(CartItemInterface $item): ?CartItemCounter
    {
        return $this->items[CartHelpers::getItemKey($item)] ?? null;
    }

    public function getItemQuantity(CartItemInterface $item): ?int
    {
        $result = $this->findItem($item);
        if (null === $result) {
            return null;

        }

        return $result->quantity;
    }


    public function hasItem(CartItemInterface $item): bool
    {
        return $this->findItem($item) !== null;
    }

    public function removeItem(CartItemInterface $item, int $qty = null): void
    {
        $id = CartHelpers::getItemKey($item);
        if (!isset($this->items[$id])) {
            return;
        }

        if ($qty === null) {
            unset($this->items[$id]);
            return;
        }

        $this->items[$id]->quantity -= $qty;
        if ($this->items[$id]->quantity <= 0) {
            unset($this->items[$id]);
        }
    }

    public function addPromotion(PromotionInterface $promotion): void
    {
        if (!$this->hasPromo($promotion)) {
            $this->promotions[CartHelpers::getItemKey($promotion)] = $promotion;
        }
    }

    public function removePromotion(PromotionInterface $promotion): void
    {
        if (isset($this->promotions[CartHelpers::getItemKey($promotion)])) {
            unset($this->promotions[CartHelpers::getItemKey($promotion)]);
        }
    }

    /**
     * @return list<CartItemInterface>
     */
    public function getItems(): array
    {
        return array_values(array_map(static fn(CartItemCounter $item): \RevoTale\ShoppingCart\CartItemInterface => $item->item, ($this->items)));
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
        return isset($this->promotions[CartHelpers::getItemKey($promotion)]);
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

        $keyedACount = array_map(static function (CartItemCounter $item): int {
            return $item->quantity;
        }, $keyedA);
        $keyedBCount = array_map(static function (CartItemCounter $item): int {
            return $item->quantity;
        }, $keyedB);

        $diff = $keyedBCount;
        foreach ($keyedACount as $itemId => $count) {
            if (!isset($diff[$itemId])) {
                $diff[$itemId] = 0;
            }

            $diff[$itemId] -= $count;

        }

        $objDiff = [];
        foreach ($diff as $itemId => $count) {
            $foundItem = null;
            foreach ($items as $item) {
                if (CartHelpers::getItemKey($item->getItem()) === $itemId) {
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
            if (CartHelpers::getItemKey($promotion) !== CartHelpers::getItemKey($promoItem)) {
                $excluded[] = $promoItem;
            }
        }

        return $excluded;
    }

    /**
     * @param list<PromotionInterface> $promotions
     * @param array<string,CartPromoImpact>  &$promoImpact
     * @param list<PromotionInterface> $notEligible
     *
     * @return list<PromotionInterface>
     */
    private function performPromotionReduce(array $promotions, array &$promoImpact, array &$notEligible): array
    {

        $newPromotions = array_values(array_filter($promotions, fn(PromotionInterface $p): bool => $p->isEligible($this)));
        $diff = CartHelpers::promoDiff($promotions, $newPromotions);
        foreach ($diff as $item) {
            if ($item['diff'] !== 0) {
                $notEligible[] = $item['item'];
            }
        }

        $promotions = $newPromotions;
        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); ++$i) {
            $promotion = $promotions[$i];
            $newPromotions = $promotion->reducePromotions(
                new ModifiedCartData(items: $this->convertToModified(array_values($this->items)), promotions: $promotions, cart: $this),
                $this->excludePromotion($promotion, $promotions)
            );

            $newPromotions[] = $promotion;


            $diff = CartHelpers::promoDiff($promotions, $newPromotions);
            if ($diff === []) {
                continue;
            }

            foreach ($diff as $item) {
                if ($item['diff'] === 0) {
                    continue 2;
                }
            }

            $promoImpact[CartHelpers::getItemKey($promotion)] = new CartPromoImpact(
                promotion: $promotion, cartItemsDiff: [], promotionsDiff: $diff
            );
            $i = 0;
            $promotions = $newPromotions;
        }

        return $promotions;
    }


    public function performTotals(): CartTotals
    {

        $items = CartHelpers::cloneItemCounters(array_values($this->items));
        /**
         * @var list<PromotionInterface> $notEligible
         */
        $notEligible = [];
        /**
         * @var array<string,CartPromoImpact> $promoImpact
         */
        $promoImpact = [];

        $promotions = $this->performPromotionReduce(array_values($this->promotions), $promoImpact, $notEligible);

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
            promotions: $keyedPromo,
            notEligible: $notEligible
        );

    }

    /**
     * @param list<PromotionInterface> $promotions
     * @param list<CartItemCounter> $items
     * @param list<CartItemPromoImpact> $promotionItemsImpact
     * @param array<string,CartItemSubTotal> $itemSubTotals
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
                $itemId = CartHelpers::getItemKey($subTotalCounter->item);
                $diff = $subTotalCounter->getSubTotal()->sub($itemSubTotals[$itemId]->subTotalAfterPromo, 5);
                if (!$diff->isZero()) {
                    $promotionItemsImpact[] = new CartItemPromoImpact(
                        item: $subTotalCounter->item,
                        promotion: $promotion,
                        priceImpact: $diff
                    );

                }
                if ($subTotalCounter->quantity > 0) {
                    $itemSubTotals[$itemId] = new CartItemSubTotal(
                        item: $subTotalCounter->item,
                        quantity: $subTotalCounter->quantity,
                        subTotalBeforePromo: $itemSubTotals[$itemId]->subTotalBeforePromo,
                        subTotalAfterPromo: $subTotalCounter->getSubTotal()
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
            static fn(CartItemCounter $item): \RevoTale\ShoppingCart\ModifiedCartItemData => new ModifiedCartItemData(
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

            $itemId = CartHelpers::getItemKey($item);
            $quantity = $this->getItemQuantity($item);
            if ($quantity === null || $quantity <= 0) {
                continue;
            }

            $before = Decimal::fromFloat($item->getUnitPrice() * $quantity, 4);

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

                $impact = $subTotal->sub($subTotalBeforePromoItem, 5);
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
        for ($i = 0; $i < count($promotions); ++$i) {
            $promotion = $promotions[$i];
            $modifiedCart = new ModifiedCartData(items: $this->convertToModified($items), promotions: $promotions, cart: $this);
            $newItems = CartHelpers::filterOutOfStockItemCounter($promotion->reduceItems($modifiedCart, $items));
            $diff = $this->itemsDiff($items, $newItems);
            $items = array_values(CartHelpers::makeKeyedItems($newItems));
            $itemId = CartHelpers::getItemKey($promotion);
            if ($diff !== []) {
                $promoImpact[$itemId] = new CartPromoImpact(
                    promotion: $promotion,
                    cartItemsDiff: $diff,
                    promotionsDiff: isset($promoImpact[$itemId]) ? $promoImpact[$itemId]->promotionsDiff : []
                );
            }
        }

        return $items;
    }

}