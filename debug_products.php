<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("DESCRIBE products");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    $stmt = $db->prepare("SELECT * FROM products WHERE name LIKE '%Avocado%'");
    $stmt->execute();
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
