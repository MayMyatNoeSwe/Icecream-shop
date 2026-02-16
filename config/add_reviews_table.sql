CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(36),
    customer_name VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NOT NULL,
    product_name VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- Insert sample reviews
INSERT INTO reviews (customer_name, rating, comment, product_name) VALUES
('Sarah Johnson', 5, 'Amazing ice cream! The vanilla flavor is so creamy and rich. The custom toppings make it even better.', 'Vanilla Ice Cream'),
('Michael Chen', 5, 'Best chocolate ice cream in town! The texture is perfect and the flavor is incredibly rich.', 'Chocolate Ice Cream'),
('Emma Wilson', 4, 'Love the pistachio flavor! Very authentic taste and great quality. The delivery was quick too.', 'Pistachio Ice Cream'),
('David Rodriguez', 5, 'The custom ice cream builder is fantastic! I love being able to choose my own flavors and toppings.', 'Custom Ice Cream'),
('Lisa Thompson', 5, 'Strawberry ice cream was delicious! Fresh taste and perfect sweetness.', 'Strawberry Ice Cream'),
('James Park', 4, 'Great variety of flavors and toppings. The matcha green tea flavor is unique and tasty.', 'Matcha Green Tea');
