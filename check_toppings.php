<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT name, category, quantity FROM products WHERE category = 'topping' ORDER BY name");
    $toppings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "TOPPINGS IN DATABASE: " . count($toppings) . "\n";
    foreach($toppings as $t) {
        echo "- {$t['name']} (Stock: {$t['quantity']})\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
