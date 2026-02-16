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
}

header('Location: cart.php');
exit;
