<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if password column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'password'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Adding password column to customers table...\n";
        $db->exec("ALTER TABLE customers ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER email");
        echo "✓ Password column added successfully!\n";
    } else {
        echo "✓ Password column already exists.\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
