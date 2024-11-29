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
        $this->items[$id] =new CartItemCounter(item: $item, quantity: $quantity);
    }

    public function getItemId(CartItemInterface $item): string
    {
        return $item->getCartId() . '________' . $item->getCartType();
    }

    public function getPromoId(PromotionInterface $promotion): string
    {
        return $promotion->getCartId() . '________' . $promotion->getCartType();
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
            $this->promotions[$this->getPromoId($promotion)] = $promotion;
        }
    }

    public function removePromotion(PromotionInterface $promotion): void
    {
        $this->promotions = array_filter($this->promotions, fn(PromotionInterface $i): bool => $this->isTheSamePromotion($promotion, $i));
    }

    public function getItems(): array
    {
        return array_map(static fn(CartItemCounter $item) => $item->item, array_values($this->items));
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
        return $this->getPromoId($promotion1) && $this->getPromoId($promotion2);
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

    public function performTotals(): CartTotals
    {
        throw new \UnexpectedValueException('not implementd');
    }
}