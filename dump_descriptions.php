<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT name, description FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $p) {
        echo $p['name'] . ": " . $p['description'] . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
