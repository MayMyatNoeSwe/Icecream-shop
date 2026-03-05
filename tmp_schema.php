<?php
require 'config/database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("DESCRIBE order_items");
print_r($stmt->fetchAll());
$stmt = $db->query("SELECT * FROM order_items LIMIT 1");
print_r($stmt->fetchAll());
