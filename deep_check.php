<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== CHECKING PRODUCT CATEGORIES ===\n\n";
    
    // Check what's labeled as "topping"
    $stmt = $db->query("SELECT id, name, category FROM products WHERE category = 'topping' ORDER BY name");
    $toppings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Products with category='topping' (" . count($toppings) . "):\n";
    foreach($toppings as $t) {
        echo "  ID: {$t['id']}, Name: {$t['name']}, Category: {$t['category']}\n";
    }
    
    echo "\n";
    
    // Check what's labeled as "size"
    $stmt = $db->query("SELECT id, name, category FROM products WHERE category = 'size' ORDER BY name");
    $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Products with category='size' (" . count($sizes) . "):\n";
    foreach($sizes as $s) {
        echo "  ID: {$s['id']}, Name: {$s['name']}, Category: {$s['category']}\n";
    }
    
    echo "\n";
    
    // Check for any products with "Cup" in the name
    $stmt = $db->query("SELECT id, name, category FROM products WHERE name LIKE '%Cup%' ORDER BY name");
    $cups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Products with 'Cup' in name (" . count($cups) . "):\n";
    foreach($cups as $c) {
        echo "  ID: {$c['id']}, Name: {$c['name']}, Category: {$c['category']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
