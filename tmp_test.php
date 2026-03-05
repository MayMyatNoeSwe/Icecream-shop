<?php
require 'config/database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT product_name FROM order_items");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($all as $row) {
    echo $row['product_name'] . "\n";
}
