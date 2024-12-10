<?php

namespace RevoTale\ShoppingCart;

final class PromoCalculationsContext
{
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $data = [];

    public function __construct()
    {
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function setValue(PromotionInterface $promotion,string $key, mixed $data): void
    {
        $promoKey =CartHelpers::getItemKey($promotion);
        if (!isset($this->data[$promoKey])) {
            $this->data[$promoKey] = [];
        }
        $this->data[$promoKey][$key] = $data;
    }


    public function getValue(PromotionInterface $promotion,string $key): mixed
    {
        return ($this->data[CartHelpers::getItemKey($promotion)] ?? null)[$key] ?? null;
    }

    public function hasValue(PromotionInterface $promotion,string $key): bool
    {
        return isset($this->data[CartHelpers::getItemKey($promotion)][$key]);
    }
}