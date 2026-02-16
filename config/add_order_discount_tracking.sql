-- Add discount tracking columns to orders table
ALTER TABLE orders 
ADD COLUMN original_subtotal DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN discount_amount DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN discount_percentage DECIMAL(5, 2) DEFAULT 0.00;

-- Add discount tracking to order_items table
ALTER TABLE order_items
ADD COLUMN original_price DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN discount_applied DECIMAL(5, 2) DEFAULT 0.00;