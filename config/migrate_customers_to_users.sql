-- Migration: Rename customers table to users and add role/permissions
USE icecream_shop;

-- Step 1: Create new users table with role and permissions
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'customer', 'staff') NOT NULL DEFAULT 'customer',
    permissions JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 2: Copy data from customers to users (if customers table exists)
INSERT INTO
    users (
        id,
        name,
        email,
        password,
        phone,
        role,
        created_at
    )
SELECT
    id,
    name,
    email,
    password,
    phone,
    'customer' as role,
    created_at
FROM customers
WHERE
    NOT EXISTS (
        SELECT 1
        FROM users
        WHERE
            users.id = customers.id
    );

-- Step 3: Update foreign key in orders table
-- First, drop the existing foreign key constraint
ALTER TABLE orders DROP FOREIGN KEY IF EXISTS orders_ibfk_1;

ALTER TABLE orders DROP FOREIGN KEY IF EXISTS `orders_ibfk_1`;

-- Rename the column from customer_id to user_id
ALTER TABLE orders
CHANGE COLUMN customer_id user_id VARCHAR(36) NOT NULL;

-- Add new foreign key constraint
ALTER TABLE orders
ADD CONSTRAINT fk_orders_user_id FOREIGN KEY (user_id) REFERENCES users (id);

-- Step 4: Drop the old customers table
DROP TABLE IF EXISTS customers;

-- Step 5: Create default admin user (password: admin123)
-- Password hash for 'admin123'
INSERT INTO
    users (
        id,
        name,
        email,
        password,
        role,
        permissions,
        phone
    )
VALUES (
        UUID(),
        'Admin User',
        'admin@scoops.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin',
        '{"manage_products": true, "manage_orders": true, "manage_users": true, "view_reports": true}',
        '09123456789'
    )
ON DUPLICATE KEY UPDATE
    id = id;

-- Display success message
SELECT 'Migration completed successfully! customers table renamed to users with role and permissions added.' AS message;