# Database Design Documentation

## Overview
This document describes the complete database schema for the Ice Cream Shop application.

## Database: `icecream_shop`

---

## Tables

### 1. `products`
Stores all products (flavors, sizes, toppings).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | VARCHAR(36) | PRIMARY KEY | Unique product identifier (UUID) |
| name | VARCHAR(255) | NOT NULL | Product name |
| description | TEXT | | Product description |
| price | DECIMAL(10,2) | NOT NULL | Base price in MMK |
| category | ENUM | NOT NULL | 'flavor', 'size', or 'topping' |
| quantity | INT | NOT NULL, DEFAULT 0 | Stock quantity |
| image_url | VARCHAR(500) | | Product image URL |
| discount_percentage | DECIMAL(5,2) | DEFAULT 0.00 | Discount percentage (0-100) |
| discount_start_date | DATETIME | NULL | When discount starts |
| discount_end_date | DATETIME | NULL | When discount ends |
| is_featured | BOOLEAN | DEFAULT FALSE | Featured on homepage |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | AUTO UPDATE | Last update timestamp |

**Indexes:**
- `idx_category` on `category`
- `idx_featured` on `is_featured`
- `idx_discount` on `discount_percentage`

---

### 2. `users`
Stores user accounts (customers, staff, admins).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | VARCHAR(36) | PRIMARY KEY | Unique user identifier (UUID) |
| name | VARCHAR(255) | NOT NULL | Full name |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Email address |
| password | VARCHAR(255) | | Hashed password |
| phone | VARCHAR(20) | | Phone number |
| role | ENUM | NOT NULL, DEFAULT 'customer' | 'admin', 'customer', or 'staff' |
| permissions | JSON | NULL | Custom permissions object |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Registration date |
| updated_at | TIMESTAMP | AUTO UPDATE | Last update timestamp |

**Indexes:**
- `idx_email` on `email`
- `idx_role` on `role`

**Roles:**
- `admin`: Full system access
- `customer`: Can place orders, view history
- `staff`: Can manage products and orders

---

### 3. `orders`
Stores customer orders.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | VARCHAR(36) | PRIMARY KEY | Unique order identifier (UUID) |
| user_id | VARCHAR(36) | NOT NULL, FK | Reference to users table |
| total_price | DECIMAL(10,2) | NOT NULL | Final total after discounts |
| original_subtotal | DECIMAL(10,2) | DEFAULT 0.00 | Subtotal before discounts |
| discount_amount | DECIMAL(10,2) | DEFAULT 0.00 | Total discount amount |
| discount_percentage | DECIMAL(5,2) | DEFAULT 0.00 | Overall discount percentage |
| order_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Order placement time |
| status | ENUM | DEFAULT 'pending' | 'pending', 'completed', 'cancelled' |
| payment_method | ENUM | NOT NULL | 'kpay', 'wavepay', 'cash' |
| order_type | ENUM | NOT NULL, DEFAULT 'delivery' | 'delivery' or 'dine-in' |
| delivery_address | TEXT | | Delivery address (if delivery) |
| delivery_township | VARCHAR(100) | | Township for delivery fee |
| delivery_fee | DECIMAL(10,2) | DEFAULT 0 | Delivery charge |
| phone | VARCHAR(20) | | Contact phone |
| notes | TEXT | | Special instructions |
| coupon_code | VARCHAR(50) | NULL | Applied coupon code |

**Indexes:**
- `idx_user_id` on `user_id`
- `idx_status` on `status`
- `idx_order_date` on `order_date`
- `idx_coupon` on `coupon_code`

**Foreign Keys:**
- `user_id` → `users(id)`

---

### 4. `order_items`
Stores individual items in each order.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique item identifier |
| order_id | VARCHAR(36) | NOT NULL, FK | Reference to orders table |
| product_id | VARCHAR(36) | NOT NULL, FK | Reference to products table |
| product_name | VARCHAR(255) | NOT NULL | Product name (snapshot) |
| price | DECIMAL(10,2) | NOT NULL | Final price after discount |
| original_price | DECIMAL(10,2) | DEFAULT 0.00 | Original price before discount |
| discount_applied | DECIMAL(5,2) | DEFAULT 0.00 | Discount percentage applied |
| quantity | INT | NOT NULL | Quantity ordered |

**Indexes:**
- `idx_order_id` on `order_id`
- `idx_product_id` on `product_id`

**Foreign Keys:**
- `order_id` → `orders(id)`
- `product_id` → `products(id)`

---

### 5. `reviews`
Stores product reviews from customers.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique review identifier |
| user_id | VARCHAR(36) | NOT NULL, FK | Reference to users table |
| product_id | VARCHAR(36) | NOT NULL, FK | Reference to products table |
| rating | INT | NOT NULL, CHECK (1-5) | Star rating (1-5) |
| comment | TEXT | | Review text |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Review date |

**Indexes:**
- `idx_product` on `product_id`
- `idx_user` on `user_id`
- `idx_rating` on `rating`

**Foreign Keys:**
- `user_id` → `users(id)` ON DELETE CASCADE
- `product_id` → `products(id)` ON DELETE CASCADE

---

### 6. `subscribers`
Stores newsletter subscribers.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique subscriber identifier |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Subscriber email |
| subscribed_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Subscription date |
| is_active | BOOLEAN | DEFAULT TRUE | Active subscription status |

**Indexes:**
- `idx_email` on `email`
- `idx_active` on `is_active`

---

### 7. `coupons`
Stores discount coupons.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique coupon identifier |
| code | VARCHAR(50) | UNIQUE, NOT NULL | Coupon code (e.g., SCOOP10) |
| discount_type | ENUM | NOT NULL, DEFAULT 'percentage' | 'percentage' or 'fixed' |
| discount_value | DECIMAL(10,2) | NOT NULL | Discount amount/percentage |
| min_order_amount | DECIMAL(10,2) | DEFAULT 0.00 | Minimum order for coupon |
| max_uses | INT | NULL | Total usage limit (NULL = unlimited) |
| max_uses_per_user | INT | DEFAULT 1 | Per-user usage limit |
| valid_from | DATETIME | NOT NULL | Coupon start date |
| valid_until | DATETIME | NOT NULL | Coupon expiry date |
| is_active | BOOLEAN | DEFAULT TRUE | Active status |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation date |

**Indexes:**
- `idx_code` on `code`
- `idx_active` on `is_active`

---

### 8. `coupon_usage`
Tracks coupon usage by users.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique usage identifier |
| coupon_id | INT | NOT NULL, FK | Reference to coupons table |
| user_id | VARCHAR(36) | NOT NULL, FK | Reference to users table |
| order_id | VARCHAR(36) | NOT NULL, FK | Reference to orders table |
| used_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Usage timestamp |

**Indexes:**
- `idx_coupon` on `coupon_id`
- `idx_user` on `user_id`

**Foreign Keys:**
- `coupon_id` → `coupons(id)` ON DELETE CASCADE
- `user_id` → `users(id)` ON DELETE CASCADE
- `order_id` → `orders(id)` ON DELETE CASCADE

---

## Entity Relationships

```
users (1) ──────< (N) orders
users (1) ──────< (N) reviews
users (1) ──────< (N) coupon_usage

products (1) ───< (N) order_items
products (1) ───< (N) reviews

orders (1) ─────< (N) order_items
orders (1) ─────< (N) coupon_usage

coupons (1) ────< (N) coupon_usage
```

---

## Key Features

### 1. Discount System
- Product-level discounts with time-based validity
- Coupon-based discounts with usage tracking
- Tracks original prices and discount amounts

### 2. Role-Based Access Control
- Three user roles: admin, customer, staff
- JSON-based permissions for fine-grained control
- Extensible permission system

### 3. Order Management
- Supports delivery and dine-in orders
- Township-based delivery fees
- Order status tracking (pending, completed, cancelled)
- Multiple payment methods

### 4. Inventory Management
- Real-time stock tracking
- Automatic inventory deduction on orders
- Stock validation before order placement

### 5. Review System
- 5-star rating system
- Text reviews for products
- User and product associations

---

## Data Integrity

### Constraints
- All prices must be non-negative
- Quantities must be non-negative
- Discount percentages must be 0-100
- Ratings must be 1-5
- Foreign key constraints ensure referential integrity

### Cascading Deletes
- Deleting a user cascades to their reviews and coupon usage
- Deleting a product cascades to its reviews
- Deleting a coupon cascades to its usage records

---

## Indexes

Indexes are strategically placed on:
- Foreign keys for JOIN performance
- Frequently queried columns (status, date, email)
- Columns used in WHERE clauses
- Columns used for sorting

---

## Default Data

### Admin User
- Email: admin@scoops.com
- Password: admin123 (hashed)
- Role: admin
- Full permissions

### Default Coupon
- Code: SCOOP10
- Type: percentage
- Value: 10%
- Max uses per user: 1
- Valid for 1 year

---

## Migration Scripts

1. `config/init.sql` - Initial database setup
2. `config/fix_database_schema.sql` - Add missing columns
3. `config/migrate_customers_to_users.sql` - Rename customers to users
4. `fix_database.php` - PHP script to run migrations

---

## Best Practices

1. **Always use prepared statements** to prevent SQL injection
2. **Hash passwords** using PHP's `password_hash()`
3. **Validate input** before database operations
4. **Use transactions** for multi-table operations
5. **Index foreign keys** for better JOIN performance
6. **Regular backups** of the database
7. **Monitor query performance** and optimize as needed

---

## Future Enhancements

Potential additions:
- `categories` table for dynamic product categories
- `addresses` table for saved delivery addresses
- `wishlists` table for favorite products
- `notifications` table for user notifications
- `audit_log` table for tracking changes
- `inventory_log` table for stock movement history
