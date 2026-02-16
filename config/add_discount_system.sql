-- Add discount columns to products table
ALTER TABLE products 
ADD COLUMN discount_percentage DECIMAL(5,2) DEFAULT 0.00,
ADD COLUMN discount_start_date DATETIME NULL,
ADD COLUMN discount_end_date DATETIME NULL,
ADD COLUMN is_featured BOOLEAN DEFAULT FALSE;

-- Update some sample products with discounts for testing
UPDATE products SET 
    discount_percentage = 20.00,
    discount_start_date = NOW(),
    discount_end_date = DATE_ADD(NOW(), INTERVAL 7 DAY),
    is_featured = TRUE
WHERE category = 'flavor' AND name LIKE '%Vanilla%' LIMIT 1;

UPDATE products SET 
    discount_percentage = 15.00,
    discount_start_date = NOW(),
    discount_end_date = DATE_ADD(NOW(), INTERVAL 5 DAY)
WHERE category = 'flavor' AND name LIKE '%Chocolate%' LIMIT 1;

UPDATE products SET 
    discount_percentage = 10.00,
    discount_start_date = NOW(),
    discount_end_date = DATE_ADD(NOW(), INTERVAL 3 DAY)
WHERE category = 'topping' LIMIT 2;