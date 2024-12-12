<?php

namespace RevoTale\ShoppingCart;

use UnexpectedValueException;

final readonly class CartHelpers
{
    public static function getItemKey(CartItemInterface|PromotionInterface $item): string
    {
        return $item->getCartId() . '________' . $item->getCartType();
    }

    public static function isTheSameItem(CartItemInterface|PromotionInterface $item1, CartItemInterface|PromotionInterface $item2): bool
    {
        return $item1->getCartId() === $item2->getCartId() && $item1->getCartType() === $item2->getCartType();
    }

    /**
     * @param list<CartItemCounter> $items
     * @return array<string,CartItemCounter>
     */
    public static function makeKeyedItems(array $items): array
    {
        /**
         * @var array<string,CartItemCounter> $result
         */
        $result = [];
        foreach ($items as $item) {

            $key = self::getItemKey($item->getItem());
            if (isset($result[$key])) {
                $result[$key]->quantity += $item->quantity;
            } else {
                $result[$key] = new CartItemCounter(
                    item: $item->item,quantity: $item->quantity
                );
            }
        }
        return $result;
    }

    /**
     * @param list<PromotionInterface> $promotions
     * @return array<string,PromotionInterface>
     */
    public static function makeKeyedPromo(array $promotions): array
    {
        $result = [];
        foreach ($promotions as $item) {
            $result[self::getItemKey($item)] = $item;
        }
        return $result;
    }

    /**
     * @param list<PromotionInterface> $a
     * @param list<PromotionInterface> $b
     * @return list<array{item:PromotionInterface,diff:int}>
     */
    public static function promoDiff(array $a, array $b): array
    {
        $keyedA = self::makeKeyedPromo($a);
        $keyedB = self::makeKeyedPromo($b);
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
                if (self::getItemKey($item) === $itemId) {
                    $foundItem = $item;
                }

            }
            if ($foundItem === null) {
                throw new UnexpectedValueException('Item not found');
            }
            if ($count !== 0) {
                $objDiff[] = [
                    'item' => $foundItem,
                    'diff' => $count
                ];
            }
        }

        return $objDiff;
    }
}