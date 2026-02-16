<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Starting migration...\n";
    
    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    if ($stmt->rowCount() > 0) {
        echo "Columns already exist. Migration skipped.\n";
        exit;
    }
    
    // Add new columns
    $sql = "ALTER TABLE orders 
            ADD COLUMN payment_method ENUM('kpay', 'wavepay', 'cash') NOT NULL DEFAULT 'cash' AFTER status,
            ADD COLUMN delivery_address TEXT AFTER payment_method,
            ADD COLUMN delivery_township VARCHAR(100) AFTER delivery_address,
            ADD COLUMN delivery_fee DECIMAL(10, 2) DEFAULT 0 AFTER delivery_township,
            ADD COLUMN phone VARCHAR(20) AFTER delivery_fee,
            ADD COLUMN notes TEXT AFTER phone";
    
    $db->exec($sql);
    
    echo "✓ Migration completed successfully!\n";
    echo "✓ Added payment_method column\n";
    echo "✓ Added delivery_address column\n";
    echo "✓ Added delivery_township column\n";
    echo "✓ Added delivery_fee column\n";
    echo "✓ Added phone column\n";
    echo "✓ Added notes column\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
}
?>
