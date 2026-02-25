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
    discount_percentage DECIMAL(5, 2) DEFAULT 0.00,
    discount_start_date DATETIME NULL,
    discount_end_date DATETIME NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_featured (is_featured),
    INDEX idx_discount (discount_percentage)
);

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'customer', 'staff') NOT NULL DEFAULT 'customer',
    permissions JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    original_subtotal DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    discount_percentage DECIMAL(5, 2) DEFAULT 0.00,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM(
        'pending',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',
    payment_method ENUM('kpay', 'wavepay', 'cash') NOT NULL,
    order_type ENUM('delivery', 'dine-in') NOT NULL DEFAULT 'delivery',
    delivery_address TEXT,
    delivery_township VARCHAR(100),
    delivery_fee DECIMAL(10, 2) DEFAULT 0,
    phone VARCHAR(20),
    notes TEXT,
    coupon_code VARCHAR(50) NULL,
    FOREIGN KEY (user_id) REFERENCES users (id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_order_date (order_date),
    INDEX idx_coupon (coupon_code)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    original_price DECIMAL(10, 2) DEFAULT 0.00,
    discount_applied DECIMAL(5, 2) DEFAULT 0.00,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders (id),
    FOREIGN KEY (product_id) REFERENCES products (id),
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
);

-- Insert sample products
INSERT INTO
    products (
        id,
        name,
        description,
        price,
        category,
        quantity,
        image_url
    )
VALUES
    -- Flavors
    (
        UUID(),
        'Vanilla',
        'Classic vanilla ice cream',
        3500,
        'flavor',
        100,
        'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Chocolate',
        'Rich chocolate ice cream',
        3500,
        'flavor',
        100,
        'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Strawberry',
        'Fresh strawberry ice cream',
        3750,
        'flavor',
        80,
        'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Mango',
        'Tropical mango ice cream',
        4000,
        'flavor',
        75,
        'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Mint Chocolate Chip',
        'Mint with chocolate chips',
        4200,
        'flavor',
        60,
        'https://images.unsplash.com/photo-1501443762994-82bd5dace89a?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Cookies and Cream',
        'Vanilla with crushed Oreo cookies',
        4500,
        'flavor',
        70,
        'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Coconut',
        'Creamy coconut ice cream',
        3800,
        'flavor',
        65,
        'https://images.unsplash.com/photo-1560008581-09826d1de69e?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Matcha Green Tea',
        'Japanese green tea flavor',
        4800,
        'flavor',
        50,
        'https://images.unsplash.com/photo-1576506295286-5cda18df43e7?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Coffee',
        'Rich espresso ice cream',
        4000,
        'flavor',
        55,
        'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Pistachio',
        'Nutty pistachio flavor',
        5000,
        'flavor',
        45,
        'https://images.unsplash.com/photo-1567206563064-6f60f40a2b57?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Salted Caramel',
        'Sweet and salty caramel',
        4500,
        'flavor',
        60,
        'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Blueberry',
        'Fresh blueberry ice cream',
        4200,
        'flavor',
        50,
        'https://images.unsplash.com/photo-1557142046-c704a3adf364?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Banana',
        'Creamy banana ice cream',
        3500,
        'flavor',
        70,
        'https://images.unsplash.com/photo-1481391319762-47dff72954d9?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Durian',
        'Authentic durian flavor',
        5500,
        'flavor',
        30,
        'https://images.unsplash.com/photo-1587049352846-4a222e784422?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Avocado',
        'Smooth avocado ice cream',
        4800,
        'flavor',
        40,
        'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400&h=300&fit=crop'
    ),
    -- Sizes
    (
        UUID(),
        'Small Cup',
        'Small serving size',
        0,
        'size',
        200,
        'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Medium Cup',
        'Medium serving size',
        1000,
        'size',
        200,
        'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Large Cup',
        'Large serving size',
        2000,
        'size',
        150,
        'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?w=400&h=300&fit=crop'
    ),
    -- Toppings
    (
        UUID(),
        'Chocolate Chips',
        'Chocolate chip topping',
        500,
        'topping',
        300,
        'https://images.unsplash.com/photo-1511381939415-e44015466834?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Sprinkles',
        'Colorful sprinkles',
        300,
        'topping',
        400,
        'https://images.unsplash.com/photo-1558326567-98ae2405596b?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Crushed Nuts',
        'Mixed crushed nuts',
        600,
        'topping',
        250,
        'https://images.unsplash.com/photo-1508737804141-4c3b688e2546?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Caramel Sauce',
        'Sweet caramel drizzle',
        400,
        'topping',
        300,
        'https://images.unsplash.com/photo-1570197788417-0e82375c9371?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Chocolate Sauce',
        'Rich chocolate syrup',
        400,
        'topping',
        300,
        'https://images.unsplash.com/photo-1481391243133-f96216dcb5d2?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Fresh Fruits',
        'Seasonal fresh fruit pieces',
        800,
        'topping',
        150,
        'https://images.unsplash.com/photo-1546548970-71785318a17b?w=400&h=300&fit=crop'
    ),
    (
        UUID(),
        'Whipped Cream',
        'Light and fluffy whipped cream',
        500,
        'topping',
        200,
        'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=400&h=300&fit=crop'
    );

-- Reviews table for product reviews
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
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating)
);

-- Subscribers table for newsletter
CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- Coupons table for discount management
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
    INDEX idx_code (code),
    INDEX idx_active (is_active)
);

-- Coupon usage tracking
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id VARCHAR(36) NOT NULL,
    order_id VARCHAR(36) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    INDEX idx_coupon (coupon_id),
    INDEX idx_user (user_id)
);

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

-- Insert default admin user (password: admin123)
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