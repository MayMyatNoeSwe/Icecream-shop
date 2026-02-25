<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT name, quantity, price, discount_percentage, is_featured FROM products WHERE category = 'flavor' ORDER BY name");
    $flavors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "CURRENT FLAVORS:\n";
    foreach ($flavors as $f) {
        echo "- {$f['name']} (Stock: {$f['quantity']}, Price: {$f['price']})" . ($f['is_featured'] ? " [FEATURED]" : "") . ($f['discount_percentage'] > 0 ? " [{$f['discount_percentage']}% OFF]" : "") . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
