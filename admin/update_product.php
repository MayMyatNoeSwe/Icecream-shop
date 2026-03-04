<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = $_POST['id'] ?? '';
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$category = $_POST['category'] ?? '';
$quantity = intval($_POST['quantity'] ?? 0);
$image_url = trim($_POST['image_url'] ?? '');
$discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
$discount_start_date = $_POST['discount_start_date'] ?? null;
$discount_end_date = $_POST['discount_end_date'] ?? null;
$is_featured = isset($_POST['is_featured']) ? 1 : 0;

// Convert empty dates to null
if (empty($discount_start_date)) $discount_start_date = null;
if (empty($discount_end_date)) $discount_end_date = null;

// Validation
if (empty($id)) {
    header('Location: product.php?error=missing_id');
    exit;
}

if (empty($name)) {
    header('Location: product.php?error=name_required');
    exit;
}

if ($price <= 0) {
    header('Location: product.php?error=invalid_price');
    exit;
}

if ($quantity < 0) {
    header('Location: product.php?error=invalid_quantity');
    exit;
}

if (!in_array($category, ['flavor', 'size', 'topping'])) {
    header('Location: product.php?error=invalid_category');
    exit;
}

if ($discount_percentage < 0 || $discount_percentage > 100) {
    header('Location: product.php?error=invalid_discount');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("UPDATE products 
                         SET name = ?, description = ?, price = ?, category = ?, quantity = ?, 
                             image_url = ?, discount_percentage = ?, discount_start_date = ?, 
                             discount_end_date = ?, is_featured = ? 
                         WHERE id = ?");
    
    $stmt->execute([
        $name, 
        $description, 
        $price, 
        $category, 
        $quantity, 
        $image_url, 
        $discount_percentage, 
        $discount_start_date, 
        $discount_end_date, 
        $is_featured, 
        $id
    ]);
    
    header('Location: product.php?success=product_updated');
    exit;
} catch (Exception $e) {
    header('Location: product.php?error=update_failed&message=' . urlencode($e->getMessage()));
    exit;
}
