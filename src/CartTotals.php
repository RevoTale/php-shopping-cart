<?php
declare(strict_types=1);

namespace RevoTale\ShoppingCart;

class CartTotals implements CartTotalsInterface
{
    /**
     * @param array<string,CartItemCounter> $items
     * @param array<string,CartItemSubTotal> $itemSubTotals
     * @param list<CartItemPromoImpact> $promotionItemsImpact
     * @param array<string,CartPromoImpact> $promotionsImpact
     * @param array<string,PromotionInterface> $promotions
     */
    public function __construct(
        protected Cart  $cart,
        protected array $items = [],
        protected array $itemSubTotals = [],
        protected array $promotionItemsImpact = [],
        protected array $promotionsImpact = [],
        protected array $promotions = [],
    )
    {
    }

    /**
     * @return list<CartItemInterface>
     */
    public function getItems(): array
    {
        return array_map(static fn(CartItemCounter $c) => $c->item, array_values($this->items));
    }

    public function getTotal(): Decimal
    {
        $total = Decimal::fromInteger(0);
        foreach ($this->itemSubTotals as $item) {
            $total = $total->add($item->subTotalAfterPromo,5);
        }

        return $total;
    }

    /**
     * @return list<PromotionInterface>
     */
    public function getPromotions(): array
    {
        return array_values($this->promotions);
    }


    public function getItemQuantity(CartItemInterface $item): int
    {
        return $this->items[$this->cart->getItemId($item)]->quantity;
    }

    /**
     * @return list<CartItemPromoImpact>
     */
    public function getPromotionItemsImpact(): array
    {
        return $this->promotionItemsImpact;
    }

    /**
     * @return list<CartPromoImpact>
     */
    public function getPromotionsImpact(): array
    {
        return array_values($this->promotionsImpact);
    }

    /**
     * @return list<CartItemSubTotal>
     */
    public function getItemSubTotals(): array
    {
        return array_values($this->itemSubTotals);
    }
}