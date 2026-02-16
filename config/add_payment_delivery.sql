-- Add payment and delivery columns to orders table
USE icecream_shop;

ALTER TABLE orders 
ADD COLUMN payment_method ENUM('kpay', 'wavepay', 'cash') NOT NULL DEFAULT 'cash' AFTER status,
ADD COLUMN delivery_address TEXT AFTER payment_method,
ADD COLUMN delivery_township VARCHAR(100) AFTER delivery_address,
ADD COLUMN delivery_fee DECIMAL(10, 2) DEFAULT 0 AFTER delivery_township,
ADD COLUMN phone VARCHAR(20) AFTER delivery_fee,
ADD COLUMN notes TEXT AFTER phone;
