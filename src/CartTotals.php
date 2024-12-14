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
     * @param list<PromotionInterface> $notEligible
     */
    public function __construct(
        protected Cart  $cart,
        protected array $items = [],
        protected array $itemSubTotals = [],
        protected array $promotionItemsImpact = [],
        protected array $promotionsImpact = [],
        protected array $promotions = [],
        protected array $notEligible = []
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


    public function getItemQuantity(CartItemInterface $item): ?int
    {
        $key = CartHelpers::getItemKey($item);
        if (isset($this->items[$key])) {
            return $this->items[$key]->quantity;
        }
        return null;
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

    public function isPromotionDiff():bool
    {
       foreach ( $this->getPromotionsImpact() as $item ) {
            if (count( $item->getAddedPromos())>0 || count( $item->getRemovedPromos())>0) {
                return true;
            }
       }
       return false;
    }
    public function isItemsDiff():bool
    {
        foreach ( $this->getPromotionsImpact() as $item ) {
            if (count( $item->getCartItemsDiff())>0) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return list<CartItemSubTotal>
     */
    public function getItemSubTotals(): array
    {
        return array_values($this->itemSubTotals);
    }

    /**
     * @return list<PromotionInterface>
     */
    public function getNotEligible(): array
    {
        return $this->notEligible;
    }
}