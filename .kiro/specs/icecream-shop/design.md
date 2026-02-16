# Design Document: Ice Cream Shop Application

## Overview

The ice cream shop application is built using PHP Object-Oriented Programming principles. The system follows a layered architecture with clear separation between domain models, business logic, data persistence, and presentation concerns. The design emphasizes SOLID principles, particularly Single Responsibility and Dependency Inversion.

## Architecture

The application uses a three-layer architecture:

1. **Domain Layer**: Core business entities (Product, Order, Cart, Customer)
2. **Service Layer**: Business logic and operations (OrderService, InventoryService, CartService)
3. **Repository Layer**: Data persistence abstraction (ProductRepository, OrderRepository)

```
┌─────────────────────────────────────┐
│     Presentation Layer (CLI/Web)    │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│        Service Layer                │
│  - OrderService                     │
│  - InventoryService                 │
│  - CartService                      │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│        Domain Layer                 │
│  - Product                          │
│  - Order                            │
│  - Cart                             │
│  - Customer                         │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│     Repository Layer                │
│  - ProductRepository                │
│  - OrderRepository                  │
│  - StorageInterface                 │
└─────────────────────────────────────┘
```

## Components and Interfaces

### Domain Models

**Product Class**
```php
class Product {
    private string $id;
    private string $name;
    private string $description;
    private float $price;
    private string $category;
    private int $quantity;
    
    public function __construct(string $name, string $description, float $price, string $category, int $quantity)
    public function getId(): string
    public function getName(): string
    public function getPrice(): float
    public function getCategory(): string
    public function getQuantity(): int
    public function setQuantity(int $quantity): void
    public function isAvailable(): bool
    public function updateDetails(string $name, string $description, float $price): void
}
```

**Cart Class**
```php
class Cart {
    private array $items; // CartItem[]
    
    public function addItem(Product $product, int $quantity): void
    public function removeItem(string $productId): void
    public function updateQuantity(string $productId, int $quantity): void
    public function getItems(): array
    public function calculateTotal(): float
    public function isEmpty(): bool
    public function clear(): void
}
```

**CartItem Class**
```php
class CartItem {
    private Product $product;
    private int $quantity;
    
    public function __construct(Product $product, int $quantity)
    public function getProduct(): Product
    public function getQuantity(): int
    public function setQuantity(int $quantity): void
    public function getSubtotal(): float
}
```

**Order Class**
```php
class Order {
    private string $id;
    private string $customerId;
    private array $items; // OrderItem[]
    private float $totalPrice;
    private DateTime $orderDate;
    
    public function __construct(string $customerId, array $items, float $totalPrice)
    public function getId(): string
    public function getCustomerId(): string
    public function getItems(): array
    public function getTotalPrice(): float
    public function getOrderDate(): DateTime
}
```

**OrderItem Class**
```php
class OrderItem {
    private string $productId;
    private string $productName;
    private float $price;
    private int $quantity;
    
    public function __construct(string $productId, string $productName, float $price, int $quantity)
    public function getProductId(): string
    public function getProductName(): string
    public function getPrice(): float
    public function getQuantity(): int
    public function getSubtotal(): float
}
```

### Service Layer

**OrderService Class**
```php
class OrderService {
    private OrderRepository $orderRepository;
    private InventoryService $inventoryService;
    
    public function __construct(OrderRepository $orderRepository, InventoryService $inventoryService)
    public function placeOrder(string $customerId, Cart $cart): Order
    public function getOrderHistory(string $customerId): array
    private function validateInventory(Cart $cart): bool
}
```

**InventoryService Class**
```php
class InventoryService {
    private ProductRepository $productRepository;
    
    public function __construct(ProductRepository $productRepository)
    public function updateQuantity(string $productId, int $quantity): void
    public function decrementInventory(string $productId, int $quantity): void
    public function checkAvailability(string $productId, int $requestedQuantity): bool
}
```

**CartService Class**
```php
class CartService {
    public function addToCart(Cart $cart, Product $product, int $quantity): void
    public function removeFromCart(Cart $cart, string $productId): void
    public function updateCartItem(Cart $cart, string $productId, int $quantity): void
    public function calculateCartTotal(Cart $cart): float
}
```

### Repository Layer

**ProductRepository Class**
```php
class ProductRepository {
    private StorageInterface $storage;
    
    public function __construct(StorageInterface $storage)
    public function save(Product $product): void
    public function findById(string $id): ?Product
    public function findAll(): array
    public function delete(string $id): void
    public function update(Product $product): void
}
```

**OrderRepository Class**
```php
class OrderRepository {
    private StorageInterface $storage;
    
    public function __construct(StorageInterface $storage)
    public function save(Order $order): void
    public function findById(string $id): ?Order
    public function findByCustomerId(string $customerId): array
}
```

**StorageInterface**
```php
interface StorageInterface {
    public function save(string $key, array $data): bool
    public function load(string $key): ?array
    public function delete(string $key): bool
    public function loadAll(string $prefix): array
}
```

**JsonFileStorage Class** (Implementation)
```php
class JsonFileStorage implements StorageInterface {
    private string $dataDirectory;
    
    public function __construct(string $dataDirectory)
    public function save(string $key, array $data): bool
    public function load(string $key): ?array
    public function delete(string $key): bool
    public function loadAll(string $prefix): array
}
```

## Data Models

### Product Data Structure
```
{
    "id": "string (UUID)",
    "name": "string",
    "description": "string",
    "price": "float (2 decimal places)",
    "category": "string (flavor|size|topping)",
    "quantity": "integer (non-negative)"
}
```

### Order Data Structure
```
{
    "id": "string (UUID)",
    "customerId": "string",
    "items": [
        {
            "productId": "string",
            "productName": "string",
            "price": "float",
            "quantity": "integer"
        }
    ],
    "totalPrice": "float (2 decimal places)",
    "orderDate": "string (ISO 8601 datetime)"
}
```

### Cart Data Structure (In-Memory)
```
{
    "items": [
        {
            "product": Product,
            "quantity": "integer"
        }
    ]
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

Before writing the correctness properties, let me analyze the acceptance criteria to determine which are testable:


### Product Management Properties

**Property 1: Product creation stores all fields**
*For any* valid product data (name, description, price, category, quantity), creating a product should result in a product object where all fields match the input data.
**Validates: Requirements 1.1**

**Property 2: Product updates persist correctly**
*For any* product and any valid update data, after updating and retrieving the product, the retrieved product should reflect the updated values.
**Validates: Requirements 1.2**

**Property 3: Product deletion removes from catalog**
*For any* product, after deletion, attempting to retrieve that product by ID should return null or indicate not found.
**Validates: Requirements 1.3**

**Property 4: Multiple categories are supported**
*For any* valid category value (flavor, size, topping), creating a product with that category should succeed and the product should have that category.
**Validates: Requirements 1.4**

**Property 5: Product retrieval returns complete data**
*For any* set of saved products, retrieving all products should return a collection where each product matches its saved data.
**Validates: Requirements 1.5**

### Inventory Management Properties

**Property 6: Zero quantity marks product unavailable**
*For any* product with quantity set to zero, the isAvailable() method should return false.
**Validates: Requirements 2.2**

**Property 7: Negative quantities are rejected**
*For any* negative quantity value, attempting to set a product's quantity should be rejected with an error.
**Validates: Requirements 2.3, 8.3**

**Property 8: Inventory updates persist**
*For any* product and valid non-negative quantity, after updating the quantity and retrieving the product, the quantity should match the updated value.
**Validates: Requirements 2.4**

**Property 9: Order placement decrements inventory**
*For any* order with specific quantities, after placing the order, each product's inventory should be decreased by the ordered quantity.
**Validates: Requirements 2.5**

### Shopping Cart Properties

**Property 10: Adding to cart stores item**
*For any* product and positive quantity, after adding to cart, the cart should contain an item with that product and quantity.
**Validates: Requirements 3.1**

**Property 11: Cart total equals sum of subtotals**
*For any* cart with items, the calculated total should equal the sum of (price × quantity) for all items.
**Validates: Requirements 3.4, 6.2**

**Property 12: Removing from cart updates contents**
*For any* product in a cart, after removing that product, the cart should no longer contain an item with that product ID.
**Validates: Requirements 3.3**

**Property 13: Quantity updates recalculate total**
*For any* cart item, after updating its quantity, the cart total should reflect the new quantity in the calculation.
**Validates: Requirements 3.2**

**Property 14: Empty cart prevents order placement**
*For any* empty cart, attempting to place an order should be rejected with an error.
**Validates: Requirements 3.5**

### Order Processing Properties

**Property 15: Insufficient inventory rejects order**
*For any* cart where at least one item's quantity exceeds available inventory, attempting to place an order should fail and return an error message.
**Validates: Requirements 4.1, 4.2**

**Property 16: Order IDs are unique**
*For any* two distinct orders, their order IDs should be different.
**Validates: Requirements 4.3**

**Property 17: Orders record timestamp**
*For any* successfully placed order, the order should have a valid order date that is not null.
**Validates: Requirements 4.4**

**Property 18: Order data persistence round-trip**
*For any* order, after saving and then retrieving it, the retrieved order should contain the same customer ID, items, quantities, and total price.
**Validates: Requirements 4.5, 7.2**

### Order History Properties

**Property 19: Placed orders appear in history**
*For any* successfully placed order, that order should appear in the order history for its customer.
**Validates: Requirements 5.1**

**Property 20: Order history filters by customer**
*For any* customer ID, the order history should contain only orders where the customer ID matches.
**Validates: Requirements 5.2**

**Property 21: Order history sorted by date descending**
*For any* order history with multiple orders, each order should have an order date greater than or equal to the next order's date.
**Validates: Requirements 5.4**

### Price Calculation Properties

**Property 22: Item subtotal equals price times quantity**
*For any* cart item or order item, the subtotal should equal the product price multiplied by the quantity.
**Validates: Requirements 6.1**

**Property 23: Prices have two decimal places precision**
*For any* calculated price or total, the value should have at most two decimal places.
**Validates: Requirements 6.3**

### Data Persistence Properties

**Property 24: Product persistence round-trip**
*For any* product, after saving to storage and then loading from storage, the loaded product should have equivalent data to the original.
**Validates: Requirements 7.1, 7.3**

**Property 25: Storage failures return errors**
*For any* storage operation that fails, the system should return an error response rather than silently failing.
**Validates: Requirements 7.5**

### Input Validation Properties

**Property 26: Empty product names are rejected**
*For any* string that is empty or contains only whitespace, attempting to create a product with that name should be rejected with an error.
**Validates: Requirements 8.1**

**Property 27: Non-positive prices are rejected**
*For any* price value that is zero or negative, attempting to set a product price should be rejected with an error.
**Validates: Requirements 8.2**

## Error Handling

The system implements comprehensive error handling:

1. **Validation Errors**: All input validation failures throw `ValidationException` with descriptive messages
2. **Inventory Errors**: Insufficient inventory throws `InsufficientInventoryException` with details about which products are unavailable
3. **Storage Errors**: Storage operation failures throw `StorageException` with information about the failed operation
4. **Not Found Errors**: Attempts to access non-existent entities throw `NotFoundException` with the entity type and ID

**Exception Hierarchy**:
```php
abstract class IceCreamShopException extends Exception {}

class ValidationException extends IceCreamShopException {}
class InsufficientInventoryException extends IceCreamShopException {}
class StorageException extends IceCreamShopException {}
class NotFoundException extends IceCreamShopException {}
```

**Error Handling Strategy**:
- All public methods document thrown exceptions
- Exceptions include context (entity IDs, validation failures)
- Repository layer catches storage-specific errors and wraps them in StorageException
- Service layer validates business rules and throws appropriate exceptions

## Testing Strategy

The testing strategy employs both unit tests and property-based tests to ensure comprehensive coverage.

### Unit Testing

Unit tests focus on:
- Specific examples demonstrating correct behavior
- Edge cases (empty carts, zero inventory, boundary values)
- Error conditions (invalid inputs, insufficient inventory)
- Integration between components (services using repositories)

Example unit test scenarios:
- Creating a product with specific values and verifying fields
- Adding multiple items to cart and checking total
- Placing an order with insufficient inventory and verifying error
- Deleting a product and confirming it's not retrievable

### Property-Based Testing

Property-based tests verify universal properties across randomized inputs using a PHP property testing library (e.g., Eris or php-quickcheck).

**Configuration**:
- Minimum 100 iterations per property test
- Each test tagged with: `Feature: icecream-shop, Property {N}: {property description}`
- Generators for: Products, Carts, Orders, valid/invalid inputs

**Property Test Coverage**:
- Each of the 27 correctness properties has a corresponding property-based test
- Tests generate random valid inputs to verify properties hold universally
- Tests generate random invalid inputs to verify validation works correctly
- Tests verify invariants (cart totals, inventory decrements, data persistence)

**Example Property Test Structure**:
```php
/**
 * Feature: icecream-shop, Property 11: Cart total equals sum of subtotals
 * 
 * @test
 */
public function testCartTotalEqualsSumOfSubtotals() {
    $this->forAll(
        Generator\cart(),
        Generator\positiveInt()
    )->then(function($cart, $iterations) {
        $expectedTotal = array_reduce(
            $cart->getItems(),
            fn($sum, $item) => $sum + $item->getSubtotal(),
            0.0
        );
        $this->assertEquals($expectedTotal, $cart->calculateTotal());
    });
}
```

### Test Organization

```
tests/
├── Unit/
│   ├── Domain/
│   │   ├── ProductTest.php
│   │   ├── CartTest.php
│   │   └── OrderTest.php
│   ├── Service/
│   │   ├── OrderServiceTest.php
│   │   ├── InventoryServiceTest.php
│   │   └── CartServiceTest.php
│   └── Repository/
│       ├── ProductRepositoryTest.php
│       └── OrderRepositoryTest.php
└── Property/
    ├── ProductPropertiesTest.php
    ├── CartPropertiesTest.php
    ├── OrderPropertiesTest.php
    ├── InventoryPropertiesTest.php
    └── PersistencePropertiesTest.php
```

### Testing Tools

- **PHPUnit**: Unit testing framework
- **Eris** or **php-quickcheck**: Property-based testing library
- **Mockery**: Mocking framework for isolating dependencies
- **PHPStan**: Static analysis for type safety

The dual testing approach ensures both concrete examples work correctly (unit tests) and universal properties hold across all inputs (property tests), providing comprehensive correctness guarantees.
