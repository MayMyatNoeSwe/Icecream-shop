-- Add order_type column to orders table
USE icecream_shop;

ALTER TABLE orders 
ADD COLUMN order_type ENUM('delivery', 'dine-in') NOT NULL DEFAULT 'delivery' AFTER payment_method;
