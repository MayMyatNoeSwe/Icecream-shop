<?php
require 'config/database.php';
$db = Database::getInstance()->getConnection();
try {
    $db->exec("ALTER TABLE coupons ADD COLUMN name VARCHAR(255) NULL AFTER id");
    echo "Column 'name' added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
