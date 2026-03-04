<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $cats = $db->query("SELECT DISTINCT category FROM products")->fetchAll(PDO::FETCH_COLUMN);
    echo "Categories: " . implode(', ', $cats) . "\n";
    
    // Check for any specific "gift" or mystery products
    $stmt = $db->query("SELECT * FROM products WHERE name LIKE '%gift%' OR name LIKE '%surprise%' OR name LIKE '%mystery%'");
    $gifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($gifts);
} catch (Exception $e) { echo $e->getMessage(); }
?>
