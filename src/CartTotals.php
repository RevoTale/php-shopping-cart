<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

class CartTotals implements CartTotalsInterface
{
    public function __construct(
        /**
         * @var list<CartItemInterface> $items
         */
        protected array $items = [],
        /**
         * @var list<CartItemSubTotal> $itemSubTotals
         */
        protected array $itemSubTotals = [],
        /**
         * @var list<CartItemPromoImpact> $promotionsImpact
         */
        protected array $promotionsImpact = [],
        /**
         * @var list<PromotionInterface> $promotions
         */
        protected array $promotions = [],
    )
    {
    }

    /**
     * @return list<CartItemInterface>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): Decimal
    {
        // TODO: Implement getTotal() method.
    }

    public function getPromotions(): array
    {
        return $this->promotions;
    }

    public function getSubTotalForItem(CartItemInterface $item): Decimal
    {
        // TODO: Implement getSubTotalForItem() method.
    }

    public function getSubTotalForPromotion(PromotionInterface $promotion): Decimal
    {
        // TODO: Implement getSubTotalForPromotion() method.
    }
}