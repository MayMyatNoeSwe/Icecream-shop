<?php
require_once 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM products WHERE category = 'flavor'");
    $flavors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($flavors);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
