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
        $id = $this->getItemId($item);
        if (isset($this->items[$id])) {
            $this->items[$id]->quantity += $quantity;
            return;
        }
        $this->items[$id] = new CartItemCounter(item: $item, quantity: $quantity);
    }

    public function getItemId(CartItemInterface|PromotionInterface $item): string
    {
        return $item->getCartId() . '________' . $item->getCartType();
    }


    private function findItem(CartItemInterface $item): ?CartItemCounter
    {
        return $this->items[$this->getItemId($item)] ?? null;
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
        $id = $this->getItemId($item);
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
            $this->promotions[$this->getItemId($promotion)] = $promotion;
        }
    }

    public function removePromotion(PromotionInterface $promotion): void
    {
        if (isset($this->promotions[$this->getItemId($promotion)])) {
            unset($this->promotions[$this->getItemId($promotion)]);
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
        return isset($this->promotions[$this->getItemId($promotion)]);
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
        $keyedA = $this->makeKeyedItems($a);
        $keyedB = $this->makeKeyedItems($b);
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
                if ($this->getItemId($item->getItem()) === $itemId) {
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
     * @param list<PromotionInterface> $a
     * @param list<PromotionInterface> $b
     * @return list<array{item:PromotionInterface,diff:int}>
     */
    private function promoDiff(array $a, array $b): array
    {
        $keyedA = $this->makeKeyedPromo($a);
        $keyedB = $this->makeKeyedPromo($b);
        $items = [];
        foreach ($keyedA as $item) {
            $items[] = $item;
        }
        foreach ($keyedB as $item) {
            $items[] = $item;
        }
        $keyedACount = array_map(static function () {
            return 1;
        }, $keyedA);
        $keyedBCount = array_map(static function () {
            return 1;
        }, $keyedB);

        $diff = $keyedBCount;
        foreach ($keyedACount as $itemId => $count) {
            $diff[$itemId] = ($diff[$itemId] ?? 0) - $count;

        }
        $objDiff = [];
        foreach ($diff as $itemId => $count) {
            $foundItem = null;
            foreach ($items as $item) {
                if ($this->getItemId($item) === $itemId) {
                    $foundItem = $item;
                }

            }
            if ($foundItem === null) {
                throw new UnexpectedValueException('Item not found');
            }
            $objDiff[] = [
                'item' => $foundItem,
                'diff' => $count
            ];
        }

        return $objDiff;
    }

    /**
     * @param list<CartItemCounter> $items
     * @return array<string,CartItemCounter>
     */
    private function makeKeyedItems(array $items): array
    {
        $result = [];
        foreach ($items as $item) {

            $result[$this->getItemId($item->getItem())] = $item;
        }
        return $result;
    }

    /**
     * @param list<PromotionInterface> $promotions
     * @return array<string,PromotionInterface>
     */
    private function makeKeyedPromo(array $promotions): array
    {
        $result = [];
        foreach ($promotions as $item) {
            $result[$this->getItemId($item)] = $item;
        }
        return $result;
    }

    /**
     * @param list<PromotionInterface> $promotions
     * @return list<PromotionInterface>
     */
    private function excludePromotion(PromotionInterface $promotion, array $promotions): array
    {
        $excluded = [];
        foreach ($promotions as $promoItem) {
            if ($this->getItemId($promotion) !== $this->getItemId($promoItem)) {
                $excluded[] = $promotion;
            }
        }
        return $excluded;
    }

    /**
     * @param list<PromotionInterface> $promotions
     * @param array<string,CartPromoImpact>  &$promoImpact
     * @return void
     */
    private function performPromotionReduce(array $promotions, array &$promoImpact): void
    {
        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); $i++) {
            $promotion = $promotions[$i];
            $newPromotions = $promotion->reducePromotions(
                new ModifiedCartData(items: $this->convertToModified(array_values($this->items)), promotions: $promotions, cart: $this),
                $this->excludePromotion($promotion, $promotions)
            );

            $diff = $this->promoDiff($promotions, $newPromotions);
            if (count($diff) === 0) {
                continue;
            }
            foreach ($diff as $item) {
                if ($item['diff'] === 0) {
                    continue 2;
                }
            }
            $promoImpact[$this->getItemId($promotion)] = new CartPromoImpact(
                promotion: $promotion, cartItemsDiff: [], promotionsDiff: $diff
            );
            $i = 0;
            $promotions = $newPromotions;
        }
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
        $this->performPromotionReduce($promotions, $promoImpact);

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


        $keyed = $this->makeKeyedItems($items);
        $keyedPromo = $this->makeKeyedPromo($promotions);
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
                $itemId = $this->getItemId($subTotalCounter->item);
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
        //TODO make something to have ability implement fixed cart discount case (when there are items cheaper, to put this price on other)
        foreach ($items as $counter) {
            $item = $counter->getItem();

            $itemId = $this->getItemId($item);
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
        $context = new PromoCalculationsContext();
        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); $i++) {
            $promotion = $promotions[$i];
            $modifiedCart = new ModifiedCartData(items: $this->convertToModified($items), promotions: $promotions, cart: $this);
            $newItems = $promotion->reduceItems($modifiedCart, $items);
            $diff = $this->itemsDiff($items, $newItems);
            $items = $newItems;
            $itemId = $this->getItemId($promotion);
            $promoImpact[$this->getItemId($promotion)] = new CartPromoImpact(
                promotion: $promotion,
                cartItemsDiff: $diff,
                promotionsDiff: isset($promoImpact[$itemId]) ? $promoImpact[$itemId]->promotionsDiff : []
            );
            if (count($diff) !== 0) {
                $i = 0;

            }
        }
        return $items;
    }

}