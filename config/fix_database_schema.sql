-- Comprehensive Database Schema Fix
-- This script adds all missing columns and indexes to the database

USE icecream_shop;

-- ============================================
-- 1. FIX PRODUCTS TABLE
-- ============================================

-- Add discount and featured columns to products
ALTER TABLE products
ADD COLUMN IF NOT EXISTS discount_percentage DECIMAL(5, 2) DEFAULT 0.00 AFTER image_url,
ADD COLUMN IF NOT EXISTS discount_start_date DATETIME NULL AFTER discount_percentage,
ADD COLUMN IF NOT EXISTS discount_end_date DATETIME NULL AFTER discount_start_date,
ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE AFTER discount_end_date;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_products_category ON products (category);

CREATE INDEX IF NOT EXISTS idx_products_featured ON products (is_featured);

CREATE INDEX IF NOT EXISTS idx_products_discount ON products (discount_percentage);

-- ============================================
-- 2. FIX USERS TABLE
-- ============================================

-- Ensure users table has all required columns
-- (Already updated in migration, but adding for completeness)
ALTER TABLE users
ADD COLUMN IF NOT EXISTS role ENUM('admin', 'customer', 'staff') NOT NULL DEFAULT 'customer' AFTER phone,
ADD COLUMN IF NOT EXISTS permissions JSON DEFAULT NULL AFTER role,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);

CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);

-- ============================================
-- 3. FIX ORDERS TABLE
-- ============================================

-- Add discount tracking columns to orders
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS original_subtotal DECIMAL(10, 2) DEFAULT 0.00 AFTER total_price,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10, 2) DEFAULT 0.00 AFTER original_subtotal,
ADD COLUMN IF NOT EXISTS discount_percentage DECIMAL(5, 2) DEFAULT 0.00 AFTER discount_amount,
ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) NULL AFTER notes;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders (user_id);

CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status);

CREATE INDEX IF NOT EXISTS idx_orders_date ON orders (order_date);

CREATE INDEX IF NOT EXISTS idx_orders_coupon ON orders (coupon_code);

-- ============================================
-- 4. FIX ORDER_ITEMS TABLE
-- ============================================

-- Add discount tracking to order_items
ALTER TABLE order_items
ADD COLUMN IF NOT EXISTS original_price DECIMAL(10, 2) DEFAULT 0.00 AFTER price,
ADD COLUMN IF NOT EXISTS discount_applied DECIMAL(5, 2) DEFAULT 0.00 AFTER original_price;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items (order_id);

CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items (product_id);

-- ============================================
-- 5. ADD MISSING TABLES
-- ============================================

-- Create reviews table if it doesn't exist
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    rating INT NOT NULL CHECK (
        rating >= 1
        AND rating <= 5
    ),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    INDEX idx_reviews_product (product_id),
    INDEX idx_reviews_user (user_id),
    INDEX idx_reviews_rating (rating)
);

-- Create subscribers table if it doesn't exist
CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_subscribers_email (email),
    INDEX idx_subscribers_active (is_active)
);

-- Create coupons table for better coupon management
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    discount_value DECIMAL(10, 2) NOT NULL,
    min_order_amount DECIMAL(10, 2) DEFAULT 0.00,
    max_uses INT DEFAULT NULL,
    max_uses_per_user INT DEFAULT 1,
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coupons_code (code),
    INDEX idx_coupons_active (is_active)
);

-- Create coupon_usage table to track coupon usage
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    order_id VARCHAR(36) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    INDEX idx_coupon_usage_coupon (coupon_id),
    INDEX idx_coupon_usage_user (user_id)
);

-- ============================================
-- 6. ADD SAMPLE DATA
-- ============================================

-- Insert default SCOOP10 coupon
INSERT INTO
    coupons (
        code,
        discount_type,
        discount_value,
        min_order_amount,
        max_uses_per_user,
        valid_from,
        valid_until,
        is_active
    )
VALUES (
        'SCOOP10',
        'percentage',
        10.00,
        0.00,
        1,
        NOW(),
        DATE_ADD(NOW(), INTERVAL 1 YEAR),
        TRUE
    )
ON DUPLICATE KEY UPDATE
    code = code;

-- ============================================
-- 7. DATA INTEGRITY CHECKS
-- ============================================

-- Update existing orders with missing discount data
UPDATE orders
SET
    original_subtotal = total_price,
    discount_amount = 0.00,
    discount_percentage = 0.00
WHERE
    original_subtotal = 0.00
    OR original_subtotal IS NULL;

-- Update existing order_items with missing discount data
UPDATE order_items
SET
    original_price = price,
    discount_applied = 0.00
WHERE
    original_price = 0.00
    OR original_price IS NULL;

-- ============================================
-- 8. ADD CONSTRAINTS FOR DATA INTEGRITY
-- ============================================

-- Ensure prices are non-negative
ALTER TABLE products
ADD CONSTRAINT chk_products_price CHECK (price >= 0),
ADD CONSTRAINT chk_products_quantity CHECK (quantity >= 0),
ADD CONSTRAINT chk_products_discount CHECK (
    discount_percentage >= 0
    AND discount_percentage <= 100
);

ALTER TABLE orders
ADD CONSTRAINT chk_orders_total CHECK (total_price >= 0),
ADD CONSTRAINT chk_orders_delivery_fee CHECK (delivery_fee >= 0);

ALTER TABLE order_items
ADD CONSTRAINT chk_order_items_price CHECK (price >= 0),
ADD CONSTRAINT chk_order_items_quantity CHECK (quantity > 0);

-- ============================================
-- VERIFICATION
-- ============================================

-- Show table structures
SELECT 'Database schema fixed successfully!' AS Status;

-- Show column counts
SELECT 'products' AS table_name, COUNT(*) AS column_count
FROM information_schema.columns
WHERE
    table_schema = 'icecream_shop'
    AND table_name = 'products'
UNION ALL
SELECT 'users' AS table_name, COUNT(*) AS column_count
FROM information_schema.columns
WHERE
    table_schema = 'icecream_shop'
    AND table_name = 'users'
UNION ALL
SELECT 'orders' AS table_name, COUNT(*) AS column_count
FROM information_schema.columns
WHERE
    table_schema = 'icecream_shop'
    AND table_name = 'orders'
UNION ALL
SELECT 'order_items' AS table_name, COUNT(*) AS column_count
FROM information_schema.columns
WHERE
    table_schema = 'icecream_shop'
    AND table_name = 'order_items';