<?php

namespace RevoTale\ShoppingCart;
use UnexpectedValueException;

/**
 * @internal
 */
final readonly class CartHelpers
{
    public static function getItemId(CartItemInterface|PromotionInterface $item): string
    {
        return $item->getCartId() . '________' . $item->getCartType();
    }

    /**
     * @param list<CartItemCounter> $items
     * @return array<string,CartItemCounter>
     */
    public static function makeKeyedItems(array $items): array
    {
        $result = [];
        foreach ($items as $item) {

            $result[self::getItemId($item->getItem())] = $item;
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
            $result[self::getItemId($item)] = $item;
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
                if (self::getItemId($item) === $itemId) {
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