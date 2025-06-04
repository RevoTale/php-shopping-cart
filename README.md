# RevoTale Shopping Cart

A powerful and flexible PHP library for implementing shopping cart functionality with advanced promotion system and precise decimal calculations.

**Notice!** This README was "vibe coded". If you encouter any errors please feel free to open the issue.

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Features

- 🛒 **Flexible Cart Management**: Add, remove, and manage cart items with quantity control
- 🏷️ **Advanced Promotion System**: Support for multiple promotion types (percentage discounts, fixed amount discounts, free products, etc.)
- 💰 **Precise Decimal Calculations**: Built-in arbitrary precision decimal arithmetic using BCMath
- 🔧 **Extensible Architecture**: Easy to extend with custom items and promotions
- 📊 **Comprehensive Totals**: Detailed cart totals with promotion impacts and item subtotals
- 🎯 **Promotion Stacking**: Support for multiple promotions that can interact with each other
- 🧮 **Mathematical Functions**: Full-featured decimal class with trigonometric, logarithmic, and power functions

## Installation

```bash
composer require revotale/shopping-cart
```

### Requirements

- PHP ^8.2
- BCMath extension

## Quick Start

### Basic Cart Operations

```php
<?php

use RevoTale\ShoppingCart\Cart;
use RevoTale\ShoppingCart\CartItemInterface;

// Create a cart item (implement CartItemInterface)
class Product implements CartItemInterface
{
    public function __construct(
        private string $id,
        private string $name,
        private int $priceInCents
    ) {}

    public function getCartId(): string
    {
        return $this->id;
    }

    public function getCartType(): string
    {
        return 'product';
    }

    public function getUnitPrice(): int
    {
        return $this->priceInCents; // Price in smallest currency unit (cents)
    }
}

// Create cart and add items
$cart = new Cart();
$product = new Product('SKU123', 'T-Shirt', 2999); // $29.99

$cart->addItem($product, 2); // Add 2 t-shirts

// Get cart totals
$totals = $cart->performTotals();
echo $totals->getTotal()->asFloat(); // 59.98
```

### Adding Promotions

```php
use RevoTale\ShoppingCart\PromotionTemplates\CartPercentageDiscount;
use RevoTale\ShoppingCart\CartInterface;

// Create a 10% discount promotion
class TenPercentDiscount extends CartPercentageDiscount
{
    public function getCartId(): string
    {
        return 'ten_percent_off';
    }

    public function getCartType(): string
    {
        return 'discount';
    }

    public function isEligible(CartInterface $cart): bool
    {
        // Apply if cart total is over $50
        return $cart->getItems() !== [] && 
               $cart->performTotals()->getTotal()->isGreaterThan(
                   \RevoTale\ShoppingCart\Decimal::fromInteger(5000)
               );
    }

    public function getDiscountMultiplier(): float
    {
        return 0.9; // 90% of original price = 10% discount
    }
}

// Add promotion to cart
$cart->addPromotion(new TenPercentDiscount());

$totals = $cart->performTotals();
echo $totals->getTotal()->asFloat(); // 53.98 (10% off $59.98)
```

## Core Components

### Cart

The main cart class that manages items and promotions.

```php
$cart = new Cart();

// Item management
$cart->addItem($item, $quantity);
$cart->removeItem($item, $quantity);
$cart->hasItem($item);
$cart->getItemQuantity($item);

// Promotion management
$cart->addPromotion($promotion);
$cart->removePromotion($promotion);
$cart->hasPromo($promotion);

// Clear operations
$cart->clearItems();
$cart->clearPromotions();
$cart->clear(); // Clear both items and promotions

// Calculate totals
$totals = $cart->performTotals();
```

### Cart Items

Items must implement `CartItemInterface`:

```php
interface CartItemInterface
{
    public function getCartId(): string;      // Unique identifier
    public function getCartType(): string;    // Type category
    public function getUnitPrice(): int;      // Price in smallest currency unit
}
```

### Promotions

Promotions implement `PromotionInterface` and can:

- **Reduce item prices**: Modify individual item subtotals
- **Add/remove items**: Add free products or remove items
- **Control other promotions**: Enable/disable other promotions
- **Apply cart-wide effects**: Fixed amount discounts, shipping rules

#### Built-in Promotion Templates

1. **CartPercentageDiscount**: Apply percentage discounts

```php
class MyPercentageDiscount extends CartPercentageDiscount
{
    public function getDiscountMultiplier(): float
    {
        return 0.85; // 15% discount
    }
    
    public function isEligible(CartInterface $cart): bool
    {
        return true; // Always eligible
    }
    
    // ... implement required methods
}
```

2. **CartFixedSumDiscount**: Apply fixed amount discounts

```php
class MyFixedDiscount extends CartFixedSumDiscount
{
    public function getDiscountAmount(): float
    {
        return 10.00; // $10 off
    }
    
    public function isEligible(CartInterface $cart): bool
    {
        return true;
    }
    
    // ... implement required methods
}
```

#### Custom Promotions

For complex promotion logic, implement `PromotionInterface` directly:

```php
class BuyOneGetOneFree implements PromotionInterface
{
    public function isEligible(CartInterface $cart): bool
    {
        return $cart->getItemQuantity($this->targetItem) >= 2;
    }

    public function reduceItems(ModifiedCartData $cart, array $itemCounters): array
    {
        // Add free items based on cart contents
        foreach ($itemCounters as $counter) {
            if ($counter->item === $this->targetItem) {
                $freeQuantity = intval($counter->quantity / 2);
                if ($freeQuantity > 0) {
                    $itemCounters[] = new CartItemCounter($this->freeItem, $freeQuantity);
                }
            }
        }
        return $itemCounters;
    }
    
    // ... implement other required methods
}
```

### Decimal Arithmetic

The library includes a comprehensive `Decimal` class for precise calculations:

```php
use RevoTale\ShoppingCart\Decimal;

// Create decimals
$price = Decimal::fromFloat(29.99);
$quantity = Decimal::fromInteger(3);
$discount = Decimal::fromString("0.1");

// Arithmetic operations
$subtotal = $price->mul($quantity);           // 89.97
$discountAmount = $subtotal->mul($discount);  // 8.997
$total = $subtotal->sub($discountAmount);     // 80.973

// Rounding and formatting
$finalTotal = $total->round(2);               // 80.97
echo $finalTotal->asFloat();                  // 80.97

// Comparisons
if ($total->isGreaterThan(Decimal::fromInteger(80))) {
    echo "Total exceeds $80";
}

// Mathematical functions
$sqrt = Decimal::fromInteger(16)->sqrt();     // 4.0
$log = Decimal::fromInteger(100)->log10();    // 2.0
$power = Decimal::fromInteger(2)->pow(Decimal::fromInteger(8)); // 256.0
```

## Advanced Usage

### Promotion Context

Use `PromoCalculationsContext` to share data between promotions during calculation:

```php
public function reduceItemSubtotal(
    ModifiedCartData $cart, 
    CartItemInterface $item, 
    Decimal $subTotal, 
    PromoCalculationsContext $context
): Decimal {
    // Store data for later use
    $context->setValue($this, 'discount_applied', true);
    
    // Retrieve data from other promotions
    $previousDiscount = $context->getValue($otherPromotion, 'discount_amount');
    
    return $subTotal->mul(Decimal::fromFloat(0.9));
}
```

### Cart Totals Analysis

The `CartTotals` object provides detailed information:

```php
$totals = $cart->performTotals();

// Basic totals
$grandTotal = $totals->getTotal();
$items = $totals->getItems();
$promotions = $totals->getPromotions();

// Detailed item information
foreach ($totals->getItemSubTotals() as $itemSubTotal) {
    echo sprintf(
        "Item: %s, Qty: %d, Before: %s, After: %s\n",
        $itemSubTotal->item->getCartId(),
        $itemSubTotal->quantity,
        $itemSubTotal->subTotalBeforePromo->asFloat(),
        $itemSubTotal->subTotalAfterPromo->asFloat()
    );
}

// Promotion impacts
foreach ($totals->getPromotionItemsImpact() as $impact) {
    echo sprintf(
        "Promotion %s affected %s by %s\n",
        $impact->promotion->getCartId(),
        $impact->item->getCartId(),
        $impact->priceImpact->asFloat()
    );
}

// Check for changes
if ($totals->isPromotionDiff()) {
    echo "Promotions were added or removed during calculation\n";
}

if ($totals->isItemsDiff()) {
    echo "Items were added or removed during calculation\n";
}
```

### Promotion Execution Order

Promotions are executed in the order they were added to the cart. Later promotions can modify the effects of earlier ones. Use `reducePromotions()` to control which other promotions are active:

```php
public function reducePromotions(ModifiedCartData $cart, array $promotions): array
{
    // Remove conflicting promotions
    return array_filter($promotions, function($promo) {
        return !($promo instanceof ConflictingPromotionType);
    });
}
```

## Error Handling

The library throws specific exceptions for various error conditions:

```php
try {
    // Division by zero
    $result = $decimal->div(Decimal::fromInteger(0));
} catch (DomainException $e) {
    echo "Mathematical error: " . $e->getMessage();
}

try {
    // Invalid number format
    $decimal = Decimal::fromString("not-a-number");
} catch (UnexpectedValueException $e) {
    echo "Invalid input: " . $e->getMessage();
}
```

## Performance Considerations

- The library uses BCMath for precise decimal calculations, which is slower than float arithmetic but provides exact results
- Promotion calculations are performed each time `performTotals()` is called
- For high-performance scenarios, consider caching totals when cart contents haven't changed
- The `Decimal` class is immutable, so operations create new instances

## Testing

```bash
# Run tests
composer run phpunit

# Run static analysis
composer run phpstan

# Run code style fixes
composer run rector:fix
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

This library is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For questions, issues, or feature requests, please use the GitHub issue tracker.
