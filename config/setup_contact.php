<?php
require_once 'database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Contact Messages Table
    $db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('pending', 'read', 'replied', 'archived') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Catering Inquiries Table
    $db->exec("CREATE TABLE IF NOT EXISTS catering_inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(36) NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        event_type VARCHAR(100) NOT NULL,
        guests INT NOT NULL,
        status ENUM('pending', 'contacted', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    
    echo "Tables created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
