<?php

namespace RevoTale\ShoppingCart;

final readonly class ModifiedCartData
{
    /**
     * @param list<ModifiedCartItemData> $items
     * @param list<PromotionInterface> $promotions
     */
    public function __construct(
        public array $items,
        public array $promotions,
        public Cart $cart
    )
    {
    }
    public function getItemQuantity(CartItemInterface $item): int
    {
        foreach ($this->items as $iItem) {
            if ($this->cart->getItemId($iItem->item) === $this->cart->getItemId($item)) {
                return $iItem->quantity;
            }
        }
        return 0;
    }
    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->quantity;
        }
        return $total;
    }
}