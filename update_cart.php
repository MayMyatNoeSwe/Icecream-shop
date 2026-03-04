<?php
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['index'], $_POST['action'])) {
    $index = (int)$_POST['index'];
    $action = $_POST['action'];
    
    if (isset($_SESSION['cart'][$index])) {
        switch ($action) {
            case 'increase':
                $maxQty = $_SESSION['cart'][$index]['max_quantity'] ?? 999;
                if ($_SESSION['cart'][$index]['cart_quantity'] < $maxQty) {
                    $_SESSION['cart'][$index]['cart_quantity']++;
                }
                break;
            case 'decrease':
                if ($_SESSION['cart'][$index]['cart_quantity'] > 1) {
                    $_SESSION['cart'][$index]['cart_quantity']--;
                } else {
                    unset($_SESSION['cart'][$index]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                }
                break;
            case 'remove':
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
        }
    }
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        
        $cart = $_SESSION['cart'];
        $total = 0;
        $totalSavings = 0;
        $itemQty = 0;
        $itemTotal = 0;
        $itemOriginalTotal = 0;
        
        foreach ($cart as $idx => $item) {
            $lineTotal = $item['price'] * $item['cart_quantity'];
            $total += $lineTotal;
            if (isset($item['original_price']) && $item['original_price'] > $item['price']) {
                $totalSavings += ($item['original_price'] - $item['price']) * $item['cart_quantity'];
            }
            if ($idx === $index) {
                $itemQty = $item['cart_quantity'];
                $itemTotal = $lineTotal;
                $itemOriginalTotal = isset($item['original_price']) ? $item['original_price'] * $item['cart_quantity'] : 0;
            }
        }
        
        $cartDiscount = 0;
        if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']) {
            if (($_SESSION['discount_type'] ?? '') === 'percentage') {
                $cartDiscount = $total * (($_SESSION['discount_value'] ?? 0) / 100);
            } else {
                $cartDiscount = ($_SESSION['discount_value'] ?? 0);
            }
        }
        $finalTotal = $total - $cartDiscount;
        $cartCount = count($cart);
        $isEmpty = empty($cart);

        echo json_encode([
            'success' => true,
            'action' => $action,
            'index' => $index,
            'itemQty' => $itemQty,
            'itemTotal' => number_format($itemTotal, 0),
            'itemOriginalTotal' => number_format($itemOriginalTotal, 0),
            'total' => number_format($total, 0),
            'totalSavings' => number_format($totalSavings, 0),
            'cartDiscount' => number_format($cartDiscount, 0),
            'finalTotal' => number_format($finalTotal, 0),
            'cartCount' => $cartCount,
            'isEmpty' => $isEmpty
        ]);
        exit;
    }
}

header('Location: cart.php');
exit;
