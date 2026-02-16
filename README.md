# Ice Cream Shop Application

A PHP-based ice cream shop management system with customer ordering and admin management features.

## Features

### Customer Features
- Browse ice cream products by category (flavors, sizes, toppings)
- Add products to shopping cart
- Adjust quantities in cart
- Place orders with customer information
- View order history by email

### Admin Features
- Manage products (add, edit, delete)
- View all orders
- Track inventory levels
- Manage product categories

## Installation

1. **Database Setup**
   - Create a MySQL database named `icecream_shop`
   - Import the schema: `mysql -u root -p icecream_shop < config/init.sql`
   - Or run the SQL commands in `config/init.sql` manually

2. **Configure Database Connection**
   - Edit `config/database.php` if needed
   - Default settings:
     - Host: localhost
     - Database: icecream_shop
     - Username: root
     - Password: (empty)

3. **Start Server**
   ```bash
   php -S localhost:8000
   ```

4. **Access Application**
   - Customer site: http://localhost:8000
   - Admin panel: http://localhost:8000/admin/

## Project Structure

```
├── config/
│   ├── database.php      # Database connection class
│   └── init.sql          # Database schema and sample data
├── admin/
│   ├── index.php         # Product management
│   ├── add_product.php   # Add new product
│   ├── edit_product.php  # Edit product
│   ├── delete_product.php # Delete product
│   └── orders.php        # View all orders
├── index.php             # Product listing page
├── cart.php              # Shopping cart
├── checkout.php          # Order placement
├── orders.php            # Customer order history
├── add_to_cart.php       # Add to cart handler
└── update_cart.php       # Cart update handler
```

## Usage

### For Customers
1. Browse products on the home page
2. Click "Add to Cart" to add items
3. View cart and adjust quantities
4. Proceed to checkout and enter your information
5. View your orders by entering your email on the Orders page

### For Admins
1. Navigate to `/admin/`
2. Add new products with name, description, price, category, and stock
3. Edit existing products to update details or inventory
4. Delete products that are no longer available
5. View all customer orders

## Database Schema

- **products**: Ice cream products with pricing and inventory
- **customers**: Customer information
- **orders**: Order records with totals and timestamps
- **order_items**: Individual items in each order

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- PDO extension enabled
