# Implementation Plan: Ice Cream Shop Application

## Overview

This implementation plan breaks down the ice cream shop application into discrete coding tasks. The approach follows a bottom-up strategy: starting with domain models, then repositories, services, and finally integration. Each task builds incrementally, ensuring no orphaned code.

## Tasks

- [ ] 1. Set up project structure and dependencies
  - Create directory structure (src/Domain, src/Service, src/Repository, src/Exception, tests/)
  - Set up Composer with autoloading (PSR-4)
  - Install PHPUnit for testing
  - Install Eris or php-quickcheck for property-based testing
  - Create phpunit.xml configuration
  - _Requirements: 7.1, 7.2_

- [ ] 2. Implement exception hierarchy
  - Create base IceCreamShopException class
  - Create ValidationException, InsufficientInventoryException, StorageException, NotFoundException
  - _Requirements: 8.4_

- [ ] 3. Implement domain models
  - [ ] 3.1 Create Product class
    - Implement constructor with validation
    - Implement getters for all properties
    - Implement setQuantity with validation
    - Implement isAvailable method
    - Implement updateDetails method
    - Generate unique IDs using uniqid() or UUID library
    - _Requirements: 1.1, 2.1, 2.2, 8.1, 8.2_
  
  - [ ] 3.2 Write property tests for Product class
    - **Property 1: Product creation stores all fields**
    - **Validates: Requirements 1.1**
    - **Property 6: Zero quantity marks product unavailable**
    - **Validates: Requirements 2.2**
    - **Property 7: Negative quantities are rejected**
    - **Validates: Requirements 2.3, 8.3**
    - **Property 26: Empty product names are rejected**
    - **Validates: Requirements 8.1**
    - **Property 27: Non-positive prices are rejected**
    - **Validates: Requirements 8.2**
  
  - [ ] 3.3 Create CartItem class
    - Implement constructor
    - Implement getters
    - Implement setQuantity with validation
    - Implement getSubtotal calculation
    - _Requirements: 6.1_
  
  - [ ] 3.4 Create Cart class
    - Implement addItem method
    - Implement removeItem method
    - Implement updateQuantity method
    - Implement getItems, isEmpty, clear methods
    - Implement calculateTotal method
    - _Requirements: 3.1, 3.2, 3.3, 3.4_
  
  - [ ] 3.5 Write property tests for Cart class
    - **Property 10: Adding to cart stores item**
    - **Validates: Requirements 3.1**
    - **Property 11: Cart total equals sum of subtotals**
    - **Validates: Requirements 3.4, 6.2**
    - **Property 12: Removing from cart updates contents**
    - **Validates: Requirements 3.3**
    - **Property 13: Quantity updates recalculate total**
    - **Validates: Requirements 3.2**
    - **Property 22: Item subtotal equals price times quantity**
    - **Validates: Requirements 6.1**
    - **Property 23: Prices have two decimal places precision**
    - **Validates: Requirements 6.3**
  
  - [ ] 3.6 Create OrderItem class
    - Implement constructor
    - Implement getters
    - Implement getSubtotal calculation
    - _Requirements: 4.5_
  
  - [ ] 3.7 Create Order class
    - Implement constructor with validation
    - Generate unique order IDs
    - Set order date to current DateTime
    - Implement getters for all properties
    - _Requirements: 4.3, 4.4, 4.5_
  
  - [ ] 3.8 Write property tests for Order class
    - **Property 16: Order IDs are unique**
    - **Validates: Requirements 4.3**
    - **Property 17: Orders record timestamp**
    - **Validates: Requirements 4.4**

- [ ] 4. Checkpoint - Ensure domain models work correctly
  - Run all tests to verify domain models
  - Ensure all tests pass, ask the user if questions arise

- [ ] 5. Implement storage layer
  - [ ] 5.1 Create StorageInterface
    - Define save, load, delete, loadAll methods
    - _Requirements: 7.1, 7.2_
  
  - [ ] 5.2 Create JsonFileStorage implementation
    - Implement constructor with data directory path
    - Implement save method (write JSON to file)
    - Implement load method (read JSON from file)
    - Implement delete method (remove file)
    - Implement loadAll method (scan directory and load matching files)
    - Handle file I/O errors and throw StorageException
    - _Requirements: 7.1, 7.2, 7.5_
  
  - [ ] 5.3 Write unit tests for JsonFileStorage
    - Test successful save and load operations
    - Test delete operations
    - Test loadAll with multiple files
    - Test error handling for file I/O failures
    - _Requirements: 7.5_

- [ ] 6. Implement repository layer
  - [ ] 6.1 Create ProductRepository class
    - Implement constructor with StorageInterface dependency
    - Implement save method (serialize Product to array)
    - Implement findById method (deserialize array to Product)
    - Implement findAll method (load all products)
    - Implement delete method
    - Implement update method
    - _Requirements: 1.1, 1.2, 1.3, 1.5, 7.1_
  
  - [ ] 6.2 Write property tests for ProductRepository
    - **Property 2: Product updates persist correctly**
    - **Validates: Requirements 1.2**
    - **Property 3: Product deletion removes from catalog**
    - **Validates: Requirements 1.3**
    - **Property 5: Product retrieval returns complete data**
    - **Validates: Requirements 1.5**
    - **Property 24: Product persistence round-trip**
    - **Validates: Requirements 7.1, 7.3**
  
  - [ ] 6.3 Create OrderRepository class
    - Implement constructor with StorageInterface dependency
    - Implement save method (serialize Order to array)
    - Implement findById method (deserialize array to Order)
    - Implement findByCustomerId method (filter orders by customer)
    - Sort results by date descending
    - _Requirements: 4.5, 5.1, 5.2, 5.4, 7.2_
  
  - [ ] 6.4 Write property tests for OrderRepository
    - **Property 18: Order data persistence round-trip**
    - **Validates: Requirements 4.5, 7.2**
    - **Property 20: Order history filters by customer**
    - **Validates: Requirements 5.2**
    - **Property 21: Order history sorted by date descending**
    - **Validates: Requirements 5.4**

- [ ] 7. Checkpoint - Ensure persistence layer works correctly
  - Run all tests to verify repositories
  - Ensure all tests pass, ask the user if questions arise

- [ ] 8. Implement service layer
  - [ ] 8.1 Create InventoryService class
    - Implement constructor with ProductRepository dependency
    - Implement updateQuantity method with validation
    - Implement decrementInventory method
    - Implement checkAvailability method
    - _Requirements: 2.3, 2.4, 2.5, 4.1_
  
  - [ ] 8.2 Write property tests for InventoryService
    - **Property 8: Inventory updates persist**
    - **Validates: Requirements 2.4**
    - **Property 9: Order placement decrements inventory**
    - **Validates: Requirements 2.5**
  
  - [ ] 8.3 Create CartService class
    - Implement addToCart method with validation
    - Implement removeFromCart method
    - Implement updateCartItem method
    - Implement calculateCartTotal method
    - _Requirements: 3.1, 3.2, 3.3, 3.4_
  
  - [ ] 8.4 Create OrderService class
    - Implement constructor with OrderRepository and InventoryService dependencies
    - Implement placeOrder method with validation
    - Validate cart is not empty
    - Validate inventory availability for all items
    - Create Order from Cart items
    - Decrement inventory for each item
    - Save order to repository
    - Throw InsufficientInventoryException if inventory insufficient
    - Implement getOrderHistory method
    - _Requirements: 3.5, 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2_
  
  - [ ] 8.5 Write property tests for OrderService
    - **Property 14: Empty cart prevents order placement**
    - **Validates: Requirements 3.5**
    - **Property 15: Insufficient inventory rejects order**
    - **Validates: Requirements 4.1, 4.2**
    - **Property 19: Placed orders appear in history**
    - **Validates: Requirements 5.1**

- [ ] 9. Checkpoint - Ensure service layer works correctly
  - Run all tests to verify services
  - Test integration between services and repositories
  - Ensure all tests pass, ask the user if questions arise

- [ ] 10. Create example usage and integration
  - [ ] 10.1 Create example CLI script or simple interface
    - Demonstrate creating products
    - Demonstrate adding items to cart
    - Demonstrate placing orders
    - Demonstrate viewing order history
    - Show error handling examples
    - _Requirements: All requirements (integration demonstration)_
  
  - [ ] 10.2 Create initialization script
    - Set up data directory
    - Create sample products
    - Initialize storage
    - _Requirements: 7.4_
  
  - [ ] 10.3 Write integration tests
    - Test complete flow: create product → add to cart → place order → verify inventory
    - Test error scenarios: insufficient inventory, invalid inputs
    - Test order history retrieval
    - _Requirements: All requirements (end-to-end validation)_

- [ ] 11. Final checkpoint - Complete system validation
  - Run all unit tests and property tests
  - Verify all 27 correctness properties pass
  - Test example usage script
  - Ensure all tests pass, ask the user if questions arise

## Notes

- Each task references specific requirements for traceability
- Property tests use minimum 100 iterations to ensure comprehensive coverage
- All property tests are tagged with feature name and property number
- Domain models are implemented first to establish core business logic
- Repositories abstract storage concerns from business logic
- Services coordinate between domain models and repositories
- Integration tasks wire everything together for end-to-end functionality
