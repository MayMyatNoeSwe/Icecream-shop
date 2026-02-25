<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM products WHERE quantity > 0 ORDER BY category, name");
    $products = $stmt->fetchAll();
    
    // Group products by category
    $productsByCategory = [];
    foreach ($products as $product) {
        $productsByCategory[$product['category']][] = $product;
    }
    
    echo "PRODUCTS BY CATEGORY:\n\n";
    foreach ($productsByCategory as $category => $items) {
        echo strtoupper($category) . " (" . count($items) . " items):\n";
        foreach ($items as $item) {
            echo "  - {$item['name']}\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
