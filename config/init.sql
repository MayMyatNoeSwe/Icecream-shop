CREATE DATABASE IF NOT EXISTS icecream_shop;
USE icecream_shop;

CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category ENUM('flavor', 'size', 'topping') NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(36) PRIMARY KEY,
    customer_id VARCHAR(36) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('kpay', 'wavepay', 'cash') NOT NULL,
    order_type ENUM('delivery', 'dine-in') NOT NULL DEFAULT 'delivery',
    delivery_address TEXT,
    delivery_township VARCHAR(100),
    delivery_fee DECIMAL(10, 2) DEFAULT 0,
    phone VARCHAR(20),
    notes TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert sample products
INSERT INTO products (id, name, description, price, category, quantity, image_url) VALUES
-- Flavors
(UUID(), 'Vanilla', 'Classic vanilla ice cream', 3500, 'flavor', 100, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop'),
(UUID(), 'Chocolate', 'Rich chocolate ice cream', 3500, 'flavor', 100, 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&h=300&fit=crop'),
(UUID(), 'Strawberry', 'Fresh strawberry ice cream', 3750, 'flavor', 80, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&h=300&fit=crop'),
(UUID(), 'Mango', 'Tropical mango ice cream', 4000, 'flavor', 75, 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=400&h=300&fit=crop'),
(UUID(), 'Mint Chocolate Chip', 'Refreshing mint with chocolate chips', 4200, 'flavor', 60, 'https://images.unsplash.com/photo-1501443762994-82bd5dace89a?w=400&h=300&fit=crop'),
(UUID(), 'Cookies and Cream', 'Vanilla with crushed Oreo cookies', 4500, 'flavor', 70, 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=400&h=300&fit=crop'),
(UUID(), 'Coconut', 'Creamy coconut ice cream', 3800, 'flavor', 65, 'https://images.unsplash.com/photo-1560008581-09826d1de69e?w=400&h=300&fit=crop'),
(UUID(), 'Matcha Green Tea', 'Japanese green tea flavor', 4800, 'flavor', 50, 'https://images.unsplash.com/photo-1576506295286-5cda18df43e7?w=400&h=300&fit=crop'),
(UUID(), 'Coffee', 'Rich espresso ice cream', 4000, 'flavor', 55, 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?w=400&h=300&fit=crop'),
(UUID(), 'Pistachio', 'Nutty pistachio flavor', 5000, 'flavor', 45, 'https://images.unsplash.com/photo-1567206563064-6f60f40a2b57?w=400&h=300&fit=crop'),
(UUID(), 'Salted Caramel', 'Sweet and salty caramel', 4500, 'flavor', 60, 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400&h=300&fit=crop'),
(UUID(), 'Blueberry', 'Fresh blueberry ice cream', 4200, 'flavor', 50, 'https://images.unsplash.com/photo-1557142046-c704a3adf364?w=400&h=300&fit=crop'),
(UUID(), 'Banana', 'Creamy banana ice cream', 3500, 'flavor', 70, 'https://images.unsplash.com/photo-1481391319762-47dff72954d9?w=400&h=300&fit=crop'),
(UUID(), 'Durian', 'Authentic durian flavor', 5500, 'flavor', 30, 'https://images.unsplash.com/photo-1587049352846-4a222e784422?w=400&h=300&fit=crop'),
(UUID(), 'Avocado', 'Smooth avocado ice cream', 4800, 'flavor', 40, 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400&h=300&fit=crop'),
-- Sizes
(UUID(), 'Small Cup', 'Small serving size', 0, 'size', 200, 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&h=300&fit=crop'),
(UUID(), 'Medium Cup', 'Medium serving size', 1000, 'size', 200, 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&h=300&fit=crop'),
(UUID(), 'Large Cup', 'Large serving size', 2000, 'size', 150, 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&h=300&fit=crop'),
-- Toppings
(UUID(), 'Chocolate Chips', 'Chocolate chip topping', 500, 'topping', 300, 'https://images.unsplash.com/photo-1511381939415-e44015466834?w=400&h=300&fit=crop'),
(UUID(), 'Sprinkles', 'Colorful sprinkles', 300, 'topping', 400, 'https://images.unsplash.com/photo-1558326567-98ae2405596b?w=400&h=300&fit=crop'),
(UUID(), 'Crushed Nuts', 'Mixed crushed nuts', 600, 'topping', 250, 'https://images.unsplash.com/photo-1508737804141-4c3b688e2546?w=400&h=300&fit=crop'),
(UUID(), 'Caramel Sauce', 'Sweet caramel drizzle', 400, 'topping', 300, 'https://images.unsplash.com/photo-1570197788417-0e82375c9371?w=400&h=300&fit=crop'),
(UUID(), 'Chocolate Sauce', 'Rich chocolate syrup', 400, 'topping', 300, 'https://images.unsplash.com/photo-1481391243133-f96216dcb5d2?w=400&h=300&fit=crop'),
(UUID(), 'Fresh Fruits', 'Seasonal fresh fruit pieces', 800, 'topping', 150, 'https://images.unsplash.com/photo-1546548970-71785318a17b?w=400&h=300&fit=crop'),
(UUID(), 'Whipped Cream', 'Light and fluffy whipped cream', 500, 'topping', 200, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&h=300&fit=crop');
