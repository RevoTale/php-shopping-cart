<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

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
     * @param list<PromotionInterface|CartItemInterface> $a
     * @param list<PromotionInterface|CartItemInterface> $b
     */
    private function arrayEqual(array $a, array $b): bool
    {
        $aIds = array_map(fn(PromotionInterface|CartItemInterface $item) => $this->getItemId($item), $a);
        $bIds = array_map(fn(PromotionInterface|CartItemInterface $item) => $this->getItemId($item), $b);

        return (
            count($aIds) === count($bIds)
            && array_diff($aIds, $bIds) === array_diff($bIds, $aIds)
        );
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
     * @param list<CartItemCounter> $items
     * @return list<CartItemInterface>
     */
    private function extractItemsCounter(array $items): array
    {
        return array_map(static fn(CartItemCounter $item): CartItemInterface => $item->getItem(), $items);

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
            if (!$this->arrayEqual($promotions, $newPromotions)) {
                $promotions = $newPromotions;
                $i = 0;
            }
        }

        /** @noinspection SlowArrayOperationsInLoopInspection */
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < count($promotions); $i++) {
            $promotion = $promotions[$i];
            $newItems = $promotion->reduceItems($this, $items);
            if (!$this->arrayEqual($this->extractItemsCounter($items), $this->extractItemsCounter($newItems))) {
                $items = $newItems;
                $i = 0;
            }
        }


        /**
         * @var array<string,CartPromoImpact> $promoImpact
         */
        $promotionItemsImpact = [];
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