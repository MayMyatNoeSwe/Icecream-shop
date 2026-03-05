<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    header('Location: login.php?redirect=' . urlencode('index.php'));
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flavorId = $_POST['flavor_id'] ?? '';
    $flavorPrice = floatval($_POST['flavor_price'] ?? 0);
    $sizeId = $_POST['size_id'] ?? '';
    $toppings = $_POST['toppings'] ?? [];
    
    $editIndex = isset($_POST['edit_index']) ? (int)$_POST['edit_index'] : -1;
    
    if (empty($flavorId) || empty($sizeId)) {
        header('Location: index_custom.php?error=missing_required');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get flavor details
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND category = 'flavor' AND quantity > 0");
        $stmt->execute([$flavorId]);
        $flavor = $stmt->fetch();
        
        // Get size details
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND category = 'size' AND quantity > 0");
        $stmt->execute([$sizeId]);
        $size = $stmt->fetch();
        
        if (!$flavor || !$size) {
            header('Location: index_custom.php?error=invalid_products');
            exit;
        }
        
        // Calculate total price using discounted prices
        $flavorDiscountedPrice = getDiscountedPrice($flavor);
        $sizeDiscountedPrice = getDiscountedPrice($size);
        $totalPrice = $flavorDiscountedPrice + $sizeDiscountedPrice;
        $originalTotalPrice = $flavor['price'] + $size['price'];
        
        $orderName = $flavor['name'] . ' (' . $size['name'] . ')';
        
        // Get topping details and add to price
        $toppingNames = [];
        $toppingsDetails = [];
        if (!empty($toppings)) {
            $placeholders = str_repeat('?,', count($toppings) - 1) . '?';
            $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND category = 'topping' AND quantity > 0");
            $stmt->execute($toppings);
            $toppingProducts = $stmt->fetchAll();
            
            foreach ($toppingProducts as $topping) {
                $toppingDiscountedPrice = getDiscountedPrice($topping);
                $totalPrice += $toppingDiscountedPrice;
                $originalTotalPrice += $topping['price'];
                $toppingNames[] = $topping['name'];
                $toppingsDetails[] = [
                    'id' => $topping['id'],
                    'name' => $topping['name'],
                    'price' => getDiscountedPrice($topping)
                ];
            }
            
            if (!empty($toppingNames)) {
                // Add toppings to order name for display
                $orderName .= ' + ' . implode(', ', $toppingNames);
            }
        }
        
        // Simple description without duplicating toppings
        $orderDescription = 'Custom ice cream order';
        
        // If we are editing an existing item
        if ($editIndex >= 0 && isset($_SESSION['cart'][$editIndex])) {
            $existingQty = $_SESSION['cart'][$editIndex]['cart_quantity'];
            $_SESSION['cart'][$editIndex] = [
                'id' => $_SESSION['cart'][$editIndex]['id'] ?? 'custom_' . uniqid(),
                'name' => $orderName,
                'description' => $orderDescription,
                'price' => $totalPrice,
                'original_price' => $originalTotalPrice,
                'image_url' => $flavor['image_url'],
                'cart_quantity' => $existingQty,
                'custom' => true,
                'flavor_id' => $flavorId,
                'size_id' => $sizeId,
                'size_name' => $size['name'],
                'toppings' => $toppings,
                'toppings_details' => $toppingsDetails,
                'max_quantity' => min($flavor['quantity'], $size['quantity']),
                'has_discount' => ($originalTotalPrice > $totalPrice)
            ];
            
            header('Location: cart.php?success=custom_updated');
            exit;
        }

        // Check if same custom order already exists in cart (for adding new)
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if (isset($item['custom']) && 
                $item['flavor_id'] === $flavorId && 
                $item['size_id'] === $sizeId && 
                $item['toppings'] === $toppings) {
                $item['cart_quantity']++;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => 'custom_' . uniqid(),
                'name' => $orderName,
                'description' => $orderDescription,
                'price' => $totalPrice,
                'original_price' => $originalTotalPrice,
                'image_url' => $flavor['image_url'],
                'cart_quantity' => 1,
                'custom' => true,
                'flavor_id' => $flavorId,
                'size_id' => $sizeId,
                'size_name' => $size['name'],
                'toppings' => $toppings,
                'toppings_details' => $toppingsDetails,
                'max_quantity' => min($flavor['quantity'], $size['quantity']),
                'has_discount' => ($originalTotalPrice > $totalPrice)
            ];
        }
        
        header('Location: cart.php?success=custom_added');
        exit;
        
    } catch (Exception $e) {
        header('Location: index.php?error=database_error');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>