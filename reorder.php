<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Fetch order items
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        if ($items) {
            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            $addedCount = 0;
            $skippedCount = 0;
            
            foreach ($items as $item) {
                // Fetch current product info
                $stmtProd = $db->prepare("SELECT * FROM products WHERE id = ?");
                $stmtProd->execute([$item['product_id']]);
                $product = $stmtProd->fetch();
                
                if ($product && $product['quantity'] > 0) {
                    $requestedQty = $item['quantity'];
                    $availableQty = $product['quantity'];
                    
                    // Check if already in cart
                    $found = false;
                    foreach ($_SESSION['cart'] as &$cartItem) {
                        if ($cartItem['id'] === $item['product_id']) {
                            // Cap quantity at available stock
                            $currentInCart = $cartItem['cart_quantity'];
                            $newQty = min($currentInCart + $requestedQty, $availableQty);
                            $cartItem['cart_quantity'] = $newQty;
                            $found = true;
                            $addedCount++;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        // Calculate current price if any
                        $price = $product['price'];
                        if (isset($product['discount_percentage']) && $product['discount_percentage'] > 0) {
                            $price = $product['price'] * (1 - ($product['discount_percentage'] / 100));
                        }
                        
                        // Final quantity cap
                        $finalQty = min($requestedQty, $availableQty);
                        
                        $_SESSION['cart'][] = [
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'price' => $price,
                            'original_price' => $product['price'],
                            'image_url' => $product['image_url'],
                            'cart_quantity' => $finalQty,
                            'max_quantity' => $availableQty,
                            'has_discount' => (isset($product['discount_percentage']) && $product['discount_percentage'] > 0),
                            'discount_percentage' => $product['discount_percentage'] ?? 0
                        ];
                        $addedCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }
            
            if ($addedCount > 0) {
                $msg = $skippedCount > 0 ? "reorder_partial" : "success";
                header('Location: cart.php?reorder=' . $msg);
            } else {
                header('Location: orders.php?reorder=failed_stock');
            }
            exit;
        }
    } catch (Exception $e) {
        header('Location: orders.php?reorder=error');
        exit;
    }
}

header('Location: orders.php');
exit;
