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
        $this->promotions = array_filter($this->promotions, fn(PromotionInterface $i): bool => $this->isTheSamePromotion($promotion, $i));
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
        return $this->findPromotion($promotion) !== null;
    }

    private function findPromotion(PromotionInterface $promotion): ?PromotionInterface
    {
        foreach ($this->promotions as $i) {
            if ($this->isTheSamePromotion($i, $promotion)) {
                return $i;
            }
        }
        return null;
    }

    private function isTheSamePromotion(PromotionInterface $promotion1, PromotionInterface $promotion2): bool
    {
        return $this->getItemId($promotion1) && $this->getItemId($promotion2);
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
            $objDiff[] = new CartItemDifference(item: $foundItem->item,difference: $count);
        }

        return $objDiff;
    }


    /**
     * @param list<PromotionInterface> $a
     * @param list<PromotionInterface> $b
     * @return list<PromotionInterface>
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
            $diff[$itemId] -= $count;

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
            $objDiff[] = $foundItem;
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


    public function performTotals(): CartTotals
    {
        /**
         * @var list<CartItemCounter> $items
         */
        $items = array_map(static fn(CartItemCounter $item) => clone $item, $this->items);
        $promotions = array_values($this->promotions);
        /**
         * @var array<string,CartPromoImpact> $promoImpact
         */
        $promoImpact = [];
        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); $i++) {
            $promotion = $promotions[$i];
            $newPromotions = $promotion->reducePromotions($this, $this->excludePromotion($promotion, $promotions));
            /**
             * @var list<array{item:PromotionInterface,diff:int}> $diff
             */
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
        }
        /**
         * @var array<string,CartPromoImpact> $promoImpact
         */
        $promotionItemsImpact = [];
        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); $i++) {
            $promotion = $promotions[$i];
            $newItems = $promotion->reduceItems($this, $items);
            $diff =   $this->itemsDiff($items,$newItems);
            $items = $newItems;
            $itemId = $this->getItemId($promotion);
            $promoImpact[$this->getItemId($promotion)] = new CartPromoImpact(
                promotion: $promotion,
                cartItemsDiff:$diff,
                promotionsDiff: isset($promoImpact[$itemId])?$promoImpact[$itemId]->promotionsDiff:[]
            );
            $i = 0;
        }


        return new CartTotals(
            cart: $this,
            items: $this->makeKeyedItems($items),
            itemSubTotals: [],
            promotionItemsImpact: $promotionItemsImpact,
            promotionsImpact: ($promoImpact),
            promotions: $this->makeKeyedPromo($promotions)
        );

    }
}