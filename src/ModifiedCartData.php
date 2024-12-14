<?php

declare(strict_types=1);

namespace RevoTale\ShoppingCart;

final readonly class ModifiedCartData implements ContainItemsInterface
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

    public function getItemQuantity(CartItemInterface $item): ?int
    {
        foreach ($this->items as $iItem) {
            if (CartHelpers::isTheSameItem($iItem->item,$item)) {
                return $iItem->quantity;
            }
        }

        return null;
    }

    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->quantity;
        }

        return $total;
    }

    public function getItems(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = $item->item;
        }

        return $result;
    }
}
