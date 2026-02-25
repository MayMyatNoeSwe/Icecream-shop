# Database Migration: customers → users

## Overview
This migration renames the `customers` table to `users` and adds role-based access control with permissions.

## Changes Made

### 1. Table Rename
- `customers` → `users`

### 2. New Columns Added
- `role` ENUM('admin', 'customer', 'staff') - User role
- `permissions` JSON - Custom permissions for fine-grained access control
- `updated_at` TIMESTAMP - Track last update time

### 3. Foreign Key Updates
- `orders.customer_id` → `orders.user_id`
- Updated foreign key constraint to reference `users` table

### 4. Default Admin User
A default admin user is created with:
- Email: `admin@scoops.com`
- Password: `admin123` (Please change this after first login!)
- Role: `admin`
- Permissions: Full access to all features

## How to Run the Migration

### Option 1: Using PHP Script (Recommended)
```bash
php migrate_to_users.php
```

### Option 2: Using MySQL Command Line
```bash
mysql -u your_username -p icecream_shop < config/migrate_customers_to_users.sql
```

### Option 3: Using phpMyAdmin
1. Open phpMyAdmin
2. Select the `icecream_shop` database
3. Go to the SQL tab
4. Copy and paste the contents of `config/migrate_customers_to_users.sql`
5. Click "Go" to execute

## Verification

After running the migration, verify:

1. Check if `users` table exists:
```sql
SHOW TABLES LIKE 'users';
```

2. Check table structure:
```sql
DESCRIBE users;
```

3. Verify data was copied:
```sql
SELECT COUNT(*) FROM users;
```

4. Check orders foreign key:
```sql
SHOW CREATE TABLE orders;
```

## Role Types

### admin
- Full access to all features
- Can manage products, orders, and users
- Can view reports and analytics

### customer
- Default role for registered users
- Can place orders and view their order history
- Can manage their own profile

### staff
- Can manage orders and products
- Cannot manage users or view sensitive data
- Limited administrative access

## Permissions Structure (JSON)

Example permissions for admin:
```json
{
  "manage_products": true,
  "manage_orders": true,
  "manage_users": true,
  "view_reports": true
}
```

Example permissions for staff:
```json
{
  "manage_products": true,
  "manage_orders": true,
  "manage_users": false,
  "view_reports": false
}
```

## Code Changes

All PHP files have been updated to use the `users` table:
- `register.php` - User registration
- `login.php` - User authentication
- `checkout.php` - Order creation
- `orders.php` - Order history
- `cart.php` - Cart management
- `admin/index.php` - Admin dashboard
- `admin/orders.php` - Order management

## Rollback (If Needed)

If you need to rollback the migration:

```sql
-- Rename users back to customers
RENAME TABLE users TO customers;

-- Update orders foreign key
ALTER TABLE orders DROP FOREIGN KEY fk_orders_user_id;
ALTER TABLE orders CHANGE COLUMN user_id customer_id VARCHAR(36) NOT NULL;
ALTER TABLE orders ADD CONSTRAINT orders_ibfk_1 
    FOREIGN KEY (customer_id) REFERENCES customers(id);

-- Remove role and permissions columns
ALTER TABLE customers DROP COLUMN role;
ALTER TABLE customers DROP COLUMN permissions;
ALTER TABLE customers DROP COLUMN updated_at;
```

## Important Notes

1. **Backup First**: Always backup your database before running migrations
2. **Test Environment**: Test the migration in a development environment first
3. **Admin Password**: Change the default admin password immediately after migration
4. **Existing Data**: All existing customer data will be preserved with role='customer'
5. **Foreign Keys**: The migration handles foreign key constraints automatically

## Support

If you encounter any issues during migration:
1. Check the error message carefully
2. Verify database permissions
3. Ensure no other processes are using the database
4. Check the migration log output

## Next Steps

After successful migration:
1. Login as admin (admin@scoops.com / admin123)
2. Change the admin password
3. Test user registration and login
4. Verify order creation works correctly
5. Check admin dashboard statistics
