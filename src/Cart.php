<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

class Cart implements CartInterface
{
    /**
     * @var array<string,array{item:CartItemInterface,qty:int}> $items
     */
    protected array $items = [];
    /**
     * @var list<PromotionInterface> $promotions
     */
    protected array $promotions = [];

    public function addItem(CartItemInterface $item,int $quantity = 1): void
    {
        if (!$this->hasItem($item)) {
            $this->items[] = [
                'item' => $item,
                'qty'=>$quantity
            ];
        }
    }

    /**
     * @param CartItemInterface $item
     * @return array{item:CartItemInterface,qty:int}|null
     */
    public function findItem(CartItemInterface $item): ?array
    {
        foreach ($this->items as $i) {
            if ($this->isTheSameItem($item, $i['item'])) {
                return $i;
            }
        }
        return null;
    }
    public function getItemQuantity(CartItemInterface $item): int
    {
       $result =  $this->findItem($item);
       if (null === $result) {
           return 0;

       }
       return $result['qty'];
    }

    public function isTheSameItem(CartItemInterface $item1, CartItemInterface $item2): bool
    {
        return $item1->getCartId() === $item2->getCartId() && $item1->getCartType() === $item2->getCartType();
    }

    public function hasItem(CartItemInterface $item): bool
    {
        return $this->findItem($item) !== null;
    }

    public function removeItem(CartItemInterface $item): void
    {
        $this->items = array_filter($this->items, fn(CartItemInterface $i) => $this->isTheSameItem($item, $i));
    }

    public function addPromotion(PromotionInterface $promotion): void
    {
        if (!$this->hasPromo($promotion)) {
            $this->promotions[] = $promotion;
        }
    }

    public function removePromotion(PromotionInterface $promotion): void
    {
        $this->promotions = array_filter($this->promotions, fn(CartItemInterface $i) => $this->isTheSamePromotion($promotion, $i));
    }

    public function getItems(): array
    {
        return array_map(static fn(array $item)=>$item['item'],$this->items);
    }

    public function getPromotions(): array
    {
        return $this->promotions;
    }

    public function hasPromo(PromotionInterface $promotion): bool
    {
        return $this->findPromotion($promotion) !== null;
    }

    public function findPromotion(PromotionInterface $promotion): ?PromotionInterface
    {
        foreach ($this->promotions as $i) {
            if ($this->isTheSamePromotion($i, $promotion)) {
                return $i;
            }
        }
        return null;
    }

    public function isTheSamePromotion(PromotionInterface $promotion1, PromotionInterface $promotion2): bool
    {
        return $promotion1->getCartId() === $promotion2->getCartId() && $promotion1->getCartType() === $promotion2->getCartType();
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

    public function performTotals():CartTotals
    {
        throw new \UnexpectedValueException('not implementd');
    }
}