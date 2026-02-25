<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Helper function to check if discount is active
function isDiscountActive($product) {
    if (!isset($product['discount_percentage']) || $product['discount_percentage'] <= 0) {
        return false;
    }
    return true;
}

// Helper function to calculate discounted price
function getDiscountedPrice($product) {
    if (!isDiscountActive($product)) {
        return $product['price'];
    }
    
    $discount = $product['discount_percentage'] / 100;
    return $product['price'] * (1 - $discount);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND quantity > 0");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Check if product already in cart
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] === $productId && !isset($item['custom'])) {
                    $item['cart_quantity']++;
                    // Ensure image_url is present for older cart versions
                    if (!isset($item['image_url'])) {
                        $item['image_url'] = $product['image_url'];
                    }
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => getDiscountedPrice($product),
                    'original_price' => $product['price'],
                    'image_url' => $product['image_url'],
                    'cart_quantity' => 1,
                    'max_quantity' => $product['quantity'],
                    'has_discount' => isDiscountActive($product),
                    'discount_percentage' => $product['discount_percentage'] ?? 0
                ];
            }
        }
    } catch (Exception $e) {
        die("Error adding to cart: " . $e->getMessage());
    }
}

header('Location: cart.php');
exit;
