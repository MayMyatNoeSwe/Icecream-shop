<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('checkout.php'));
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

// Simple calculation using cart data as-is
$total = 0;
$originalTotal = 0;
$totalSavings = 0;
$hasDiscounts = false;

foreach ($cart as $item) {
    $itemTotal = $item['price'] * $item['cart_quantity'];
    $total += $itemTotal;
    
    // Check if item already has discount information from when it was added to cart
    if (isset($item['original_price']) && isset($item['has_discount']) && $item['has_discount'] && $item['original_price'] > $item['price']) {
        $originalItemTotal = $item['original_price'] * $item['cart_quantity'];
        $originalTotal += $originalItemTotal;
        $totalSavings += ($originalItemTotal - $itemTotal);
        $hasDiscounts = true;
        $originalTotal += $itemTotal;
    }
}

// Apply Cart-Wide Discount (e.g. from SCOOP10 coupon)
$cartDiscount = 0;
if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']) {
    $cartDiscount = $total * 0.10;
    $totalSavings += $cartDiscount;
    $total -= $cartDiscount; // Reduce the total to be paid
}

$error = '';
$success = false;

// Get logged-in user's information
$loggedInUser = null;
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$_SESSION['user_email']]);
    $loggedInUser = $stmt->fetch();
} catch (Exception $e) {
    $error = 'Unable to load user information';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for duplicate submission protection
    $submissionToken = $_POST['submission_token'] ?? '';
    if (empty($submissionToken) || !isset($_SESSION['checkout_token']) || $submissionToken !== $_SESSION['checkout_token']) {
        $error = 'Invalid submission. Please try again.';
    } elseif (empty($cart)) {
        $error = 'Your cart is empty. Please add items before checkout.';
    } else {
        // Clear the token to prevent reuse
        unset($_SESSION['checkout_token']);
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? '';
        $orderType = $_POST['order_type'] ?? 'delivery';
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $deliveryTownship = $_POST['delivery_township'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
    
    // Calculate delivery fee based on township (only for delivery orders)
    $deliveryFee = 0;
    if ($orderType === 'delivery') {
        $yangonTownships = [
            'Hlaing' => 2000,
            'Kamayut' => 2000,
            'Mayangone' => 2500,
            'Insein' => 3000,
            'Mingaladon' => 3500,
            'Shwepyitha' => 4000,
            'Hlegu' => 5000,
            'Hmawbi' => 5000,
            'Htantabin' => 5500,
            'Taikkyi' => 6000,
            'Bahan' => 2000,
            'Dagon' => 2500,
            'Lanmadaw' => 2000,
            'Latha' => 2000,
            'Pabedan' => 2000,
            'Kyauktada' => 2000,
            'Botataung' => 2500,
            'Pazundaung' => 2500,
            'Mingala Taungnyunt' => 2500,
            'Thaketa' => 3000,
            'North Okkalapa' => 3000,
            'South Okkalapa' => 3000,
            'Thingangyun' => 2500,
            'Yankin' => 2500,
            'Tamwe' => 2500,
            'Sanchaung' => 2000,
            'Kyimyindaing' => 2000,
            'Ahlon' => 2500,
            'Dala' => 4000,
            'Seikkan' => 4000,
            'Thongwa' => 6000,
            'Kayan' => 6000,
            'Twante' => 6000,
            'Kawhmu' => 7000,
            'Kungyangon' => 8000,
            'Dagon Seikkan' => 4000,
            'North Dagon' => 3500,
            'East Dagon' => 3500,
            'South Dagon' => 3500
        ];
        
        if (isset($yangonTownships[$deliveryTownship])) {
            $deliveryFee = $yangonTownships[$deliveryTownship];
        }
    }
    
    $totalWithDelivery = $total + $deliveryFee;
    
    // Validation based on order type
    if ($orderType === 'delivery') {
        if (empty($name) || empty($email) || empty($paymentMethod) || empty($deliveryAddress) || empty($deliveryTownship)) {
            $error = 'Please fill in all required fields for delivery';
        }
    } else {
        // Dine-in only requires basic info
        if (empty($name) || empty($email) || empty($paymentMethod)) {
            $error = 'Please fill in all required fields';
        }
    }
    
    // Validate that email matches logged-in user
    if (empty($error) && $email !== $_SESSION['user_email']) {
        $error = 'Email must match your logged-in account (' . $_SESSION['user_email'] . ')';
    }
    
    if (empty($error)) {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            // Check inventory
            foreach ($cart as $item) {
                // Skip inventory check for custom orders (they're combinations)
                if (isset($item['custom']) && $item['custom'] === true) {
                    // For custom orders, check the flavor and size components
                    if (isset($item['flavor_id'])) {
                        $stmt = $db->prepare("SELECT quantity FROM products WHERE id = ?");
                        $stmt->execute([$item['flavor_id']]);
                        $flavor = $stmt->fetch();
                        if (!$flavor || $flavor['quantity'] < $item['cart_quantity']) {
                            throw new Exception("Insufficient inventory for flavor in " . $item['name']);
                        }
                    }
                    if (isset($item['size_id'])) {
                        $stmt = $db->prepare("SELECT quantity FROM products WHERE id = ?");
                        $stmt->execute([$item['size_id']]);
                        $size = $stmt->fetch();
                        if (!$size || $size['quantity'] < $item['cart_quantity']) {
                            throw new Exception("Insufficient inventory for size in " . $item['name']);
                        }
                    }
                    // Check toppings if any
                    if (isset($item['toppings']) && !empty($item['toppings'])) {
                        foreach ($item['toppings'] as $toppingId) {
                            $stmt = $db->prepare("SELECT quantity FROM products WHERE id = ?");
                            $stmt->execute([$toppingId]);
                            $topping = $stmt->fetch();
                            if (!$topping || $topping['quantity'] < $item['cart_quantity']) {
                                throw new Exception("Insufficient inventory for topping in " . $item['name']);
                            }
                        }
                    }
                } else {
                    // Regular product inventory check
                    $stmt = $db->prepare("SELECT quantity FROM products WHERE id = ?");
                    $stmt->execute([$item['id']]);
                    $product = $stmt->fetch();
                    
                    if (!$product || $product['quantity'] < $item['cart_quantity']) {
                        throw new Exception("Insufficient inventory for " . $item['name']);
                    }
                }
            }
            
            // Create or get customer
            $customerId = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO customers (id, name, email, phone) VALUES (?, ?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE id=id");
            $stmt->execute([$customerId, $name, $email, $phone]);
            
            $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            $customer = $stmt->fetch();
            $customerId = $customer['id'];
            
            // Create order with discount information
            $orderId = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO orders (id, customer_id, total_price, original_subtotal, discount_amount, discount_percentage, payment_method, order_type, delivery_address, delivery_township, delivery_fee, phone, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $discountPercentage = $originalTotal > 0 ? (($totalSavings / $originalTotal) * 100) : 0;
            
            // Allow for a slightly higher discount percent if coupon + usage (since originalTotal doesn't change with coupon, but savings do)
            // Actually, keep it simple. Total Savings / Original Total is fine.
            
            $stmt->execute([$orderId, $customerId, $totalWithDelivery, $originalTotal, $totalSavings, $discountPercentage, $paymentMethod, $orderType, $deliveryAddress, $deliveryTownship, $deliveryFee, $phone, $notes]);
            
            // Add order items with discount information
            foreach ($cart as $item) {
                // For custom orders, use the flavor_id as product_id
                $productId = (isset($item['custom']) && $item['custom'] === true && isset($item['flavor_id'])) 
                    ? $item['flavor_id'] 
                    : $item['id'];
                
                $originalPrice = isset($item['original_price']) ? $item['original_price'] : $item['price'];
                $discountApplied = isset($item['discount_percentage']) ? $item['discount_percentage'] : 0;
                
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, original_price, discount_applied, quantity) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $productId, $item['name'], $item['price'], $originalPrice, $discountApplied, $item['cart_quantity']]);
                
                // Update inventory based on item type
                if (isset($item['custom']) && $item['custom'] === true) {
                    // For custom orders, deduct from flavor, size, and toppings
                    if (isset($item['flavor_id'])) {
                        $stmt = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                        $stmt->execute([$item['cart_quantity'], $item['flavor_id']]);
                    }
                    if (isset($item['size_id'])) {
                        $stmt = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                        $stmt->execute([$item['cart_quantity'], $item['size_id']]);
                    }
                    if (isset($item['toppings']) && !empty($item['toppings'])) {
                        foreach ($item['toppings'] as $toppingId) {
                            $stmt = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                            $stmt->execute([$item['cart_quantity'], $toppingId]);
                        }
                    }
                } else {
                    // Regular product inventory deduction
                    $stmt = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                    $stmt->execute([$item['cart_quantity'], $item['id']]);
                }
            }
            
            $db->commit();
            $_SESSION['cart'] = [];
            unset($_SESSION['coupon_applied']); // Clear coupon after use
            unset($_SESSION['coupon_code']);
            
            // Redirect to orders page to prevent duplicate submissions
            header('Location: orders.php?success=order_placed');
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
    } // Close the token validation if statement
}

// Generate a unique token for this checkout session
if (!isset($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Scoops</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        
        header { 
            padding: 40px 0; 
            margin-bottom: 20px;
        }
        
        .header-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-brand {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: white;
            font-weight: 700;
            text-decoration: none;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-tabs-premium {
            display: inline-flex;
            background: rgba(0, 0, 0, 0.2);
            padding: 5px;
            border-radius: 100px;
            gap: 5px;
        }
        
        .nav-link-premium {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link-premium:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link-premium.active {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        .checkout-container { 
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 40px; 
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-group { 
            margin-bottom: 25px; 
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #2d3748;
            font-weight: 600;
            font-size: 15px;
        }
        
        input, select, textarea { 
            width: 100%; 
            padding: 15px 20px; 
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }
        
        select {
            cursor: pointer;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .form-section {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            color: #2d3748;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            color: #667eea;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid rgba(102, 126, 234, 0.2);
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            text-decoration: none;
        }
        
        .back-button i {
            transition: transform 0.3s ease;
        }
        
        .back-button:hover i {
            transform: translateX(-3px);
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
            resize: vertical;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .order-type-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .order-type-option {
            cursor: pointer;
        }
        
        .order-type-option input[type="radio"] {
            display: none;
        }
        
        .order-type-card {
            background: white;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .order-type-option input[type="radio"]:checked + .order-type-card {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .order-type-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .order-type-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }
        
        .order-type-name {
            font-weight: 700;
            color: #2d3748;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .order-type-desc {
            font-size: 13px;
            color: rgba(102, 126, 234, 0.8);
        }
        
        .payment-option {
            cursor: pointer;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-card {
            background: white;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .payment-option input[type="radio"]:checked + .payment-card {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        
        .payment-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .payment-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .payment-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 15px;
        }
        
        @media (max-width: 768px) {
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
        
        .order-summary { 
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
            backdrop-filter: blur(20px);
            padding: 35px; 
            border-radius: 20px; 
            margin: 30px 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .order-summary h2 {
            font-family: 'Playfair Display', serif;
            color: #2d3748;
            margin-bottom: 25px;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .order-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start;
            padding: 20px 0; 
            border-bottom: 1px solid rgba(102, 126, 234, 0.15);
            color: #764ba2;
            transition: all 0.3s ease;
        }
        
        .order-item:hover {
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            padding: 20px 15px;
            margin: 0 -15px;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            font-size: 16px;
            color: #2d3748;
            margin-bottom: 5px;
            line-height: 1.4;
        }
        
        .item-discount {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #c53030;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(197, 48, 48, 0.2);
        }
        
        .item-pricing {
            text-align: right;
            min-width: 120px;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #a0aec0;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .discounted-price {
            color: #e53e3e;
            font-weight: 700;
            font-size: 16px;
        }
        
        .regular-price {
            font-weight: 600;
            font-size: 16px;
            color: #2d3748;
        }
        
        .summary-totals {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid rgba(102, 126, 234, 0.2);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            font-size: 16px;
        }
        
        .summary-row.original-total {
            color: #a0aec0;
        }
        
        .summary-row.savings {
            color: #48bb78;
            font-weight: 700;
            background: rgba(72, 187, 120, 0.1);
            padding: 15px 20px;
            border-radius: 12px;
            margin: 10px 0;
        }
        
        .summary-row.subtotal {
            font-weight: 600;
            font-size: 18px;
            color: #2d3748;
        }
        
        .summary-row.delivery-fee {
            color: rgba(102, 126, 234, 0.8);
        }
        
        .total { 
            font-size: 32px; 
            font-weight: 700; 
            margin-top: 25px; 
            text-align: center;
            font-family: 'Playfair Display', serif;
            padding: 25px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 15px;
            border: 2px solid rgba(102, 126, 234, 0.2);
        }
        
        .total span {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .submit-btn { 
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white; 
            border: none; 
            padding: 18px 40px; 
            border-radius: 50px; 
            cursor: pointer; 
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.4);
        }
        
        .submit-btn:hover { 
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(72, 187, 120, 0.5);
        }
        
        .error { 
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #742a2a;
            padding: 18px; 
            border-radius: 12px; 
            margin-bottom: 25px;
            font-weight: 600;
            border: 2px solid #fc8181;
        }
        
        .success { 
            background: linear-gradient(135deg, #68d391 0%, #48bb78 100%);
            color: white;
            padding: 30px; 
            border-radius: 15px; 
            text-align: center;
        }
        
        .success h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .success a { 
            color: white;
            font-weight: 600;
            text-decoration: underline;
        }
        
        /* Delivery Section Transitions */
        #deliverySection {
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .order-type-card {
            transition: all 0.3s ease;
        }
        
        .order-type-option input:checked + .order-type-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-glass">
                <a href="index.php" class="header-brand">Scoops Creamery</a>
                <nav class="nav-tabs-premium">
                    <a href="index.php" class="nav-link-premium">Collection</a>
                    <a href="cart.php" class="nav-link-premium">Cart</a>
                    <a href="orders.php" class="nav-link-premium">My Orders</a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="checkout-container">
            <h1 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 30px; color: #2d3748; text-align: center;">Checkout</h1>
            <a href="cart.php" class="back-button">
                <i>←</i> Back to Cart
            </a>
            
            <?php if ($success): ?>
                <div class="success">
                    <h2>Order Placed Successfully!</h2>
                    <p>Thank you for your order. You will receive a confirmation email shortly.</p>
                    <p><a href="orders.php">View My Orders</a> | <a href="index.php">Continue Shopping</a></p>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="error">
                        ⚠️ <?= htmlspecialchars($error) ?>
                        <br><br>
                        <a href="cart.php" style="color: #742a2a; font-weight: 700; text-decoration: underline;">← Go back to cart</a>
                    </div>
                <?php endif; ?>
                
                <h2>Order Summary</h2>
                <div class="order-summary">
                    <?php foreach ($cart as $item): ?>
                    <div class="order-item">
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?> × <?= $item['cart_quantity'] ?></div>
                            <?php if (isset($item['has_discount']) && $item['has_discount'] && isset($item['discount_percentage'])): ?>
                                <div class="item-discount">
                                    🏷️ <?= $item['discount_percentage'] ?>% OFF
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="item-pricing">
                            <?php if (isset($item['has_discount']) && $item['has_discount'] && isset($item['original_price'])): ?>
                                <div class="original-price">
                                    <?= number_format($item['original_price'] * $item['cart_quantity'], 0) ?> MMK
                                </div>
                                <div class="discounted-price">
                                    <?= number_format($item['price'] * $item['cart_quantity'], 0) ?> MMK
                                </div>
                            <?php else: ?>
                                <div class="regular-price"><?= number_format($item['price'] * $item['cart_quantity'], 0) ?> MMK</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']): ?>
                    <div class="order-item" style="background: rgba(108, 93, 252, 0.05); border-radius: 12px; padding: 15px; margin-top: 10px;">
                        <div class="item-details">
                            <div class="item-name" style="color: #6c5dfc;">Coupon: SCOOP10</div>
                            <div style="font-size: 0.85rem; color: #6c5dfc;">10% Cart Discount Applied</div>
                        </div>
                        <div class="item-pricing">
                            <div class="discounted-price" style="color: #6c5dfc;">-<?= number_format($cartDiscount, 0) ?> MMK</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-totals">
                        <div class="summary-row subtotal">
                            <span>Subtotal (After Item Discounts)</span>
                            <span><?= number_format($total + $cartDiscount, 0) ?> MMK</span>
                        </div>
                        
                        <?php if ($cartDiscount > 0): ?>
                        <div class="summary-row savings" style="color: #6c5dfc;">
                             <span>Coupon Discount</span>
                             <span>-<?= number_format($cartDiscount, 0) ?> MMK</span>
                        </div>
                        <?php endif; ?>

                        <?php if ($totalSavings > 0): ?>
                        <div class="summary-row savings">
                            <span>Total Savings</span>
                            <span>-<?= number_format($totalSavings, 0) ?> MMK</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row delivery-fee" id="deliveryFeeRow" style="display: none;">
                            <span>Delivery Fee</span>
                            <span id="deliveryFeeAmount">0 MMK</span>
                        </div>
                    </div>
                    
                    <div class="total">Total: <span id="totalAmount"><?= number_format($total, 0) ?> MMK</span></div>
                </div>
                
                <h2 class="section-title">Customer Information</h2>
                <form method="POST" id="checkoutForm">
                    <input type="hidden" name="submission_token" value="<?= $_SESSION['checkout_token'] ?>">
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?= htmlspecialchars($loggedInUser['name'] ?? $_SESSION['user_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email * (Account Email)</label>
                            <input type="email" id="email" name="email" required readonly
                                   value="<?= htmlspecialchars($_SESSION['user_email']) ?>"
                                   style="background-color: #f7fafc; cursor: not-allowed;">
                            <small style="color: rgba(102, 126, 234, 0.7); font-size: 12px; margin-top: 5px; display: block;">
                                This email is linked to your account and cannot be changed
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" required placeholder="09xxxxxxxxx"
                                   value="<?= htmlspecialchars($loggedInUser['phone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-title">Order Type *</h2>
                        <div class="order-type-options">
                            <label class="order-type-option">
                                <input type="radio" name="order_type" value="delivery" onchange="toggleDeliveryFields()">
                                <div class="order-type-card">
                                    <div class="order-type-icon">🚚</div>
                                    <div class="order-type-name">Delivery</div>
                                    <div class="order-type-desc">Get it delivered to your door</div>
                                </div>
                            </label>
                            <label class="order-type-option">
                                <input type="radio" name="order_type" value="dine-in" checked onchange="toggleDeliveryFields()">
                                <div class="order-type-card">
                                    <div class="order-type-icon">🍽️</div>
                                    <div class="order-type-name">Dine-in</div>
                                    <div class="order-type-desc">Enjoy at our shop</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2 class="section-title">Payment Method *</h2>
                        <div class="payment-methods">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="kpay" required>
                                <div class="payment-card">
                                    <div class="payment-icon">💳</div>
                                    <div class="payment-name">KPay</div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="wavepay" required>
                                <div class="payment-card">
                                    <div class="payment-icon">📱</div>
                                    <div class="payment-name">WavePay</div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cash" required>
                                <div class="payment-card">
                                    <div class="payment-icon">💵</div>
                                    <div class="payment-name">Cash</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div id="deliverySection">
                        <div class="form-section">
                            <h2 class="section-title">Delivery Information</h2>
                        <div class="form-group">
                            <label for="delivery_township">Township (Yangon) *</label>
                            <select id="delivery_township" name="delivery_township">
                                <option value="">Select Township</option>
                            <option value="Hlaing">Hlaing (2,000 MMK)</option>
                            <option value="Kamayut">Kamayut (2,000 MMK)</option>
                            <option value="Mayangone">Mayangone (2,500 MMK)</option>
                            <option value="Insein">Insein (3,000 MMK)</option>
                            <option value="Mingaladon">Mingaladon (3,500 MMK)</option>
                            <option value="Shwepyitha">Shwepyitha (4,000 MMK)</option>
                            <option value="Hlegu">Hlegu (5,000 MMK)</option>
                            <option value="Hmawbi">Hmawbi (5,000 MMK)</option>
                            <option value="Htantabin">Htantabin (5,500 MMK)</option>
                            <option value="Taikkyi">Taikkyi (6,000 MMK)</option>
                            <option value="Bahan">Bahan (2,000 MMK)</option>
                            <option value="Dagon">Dagon (2,500 MMK)</option>
                            <option value="Lanmadaw">Lanmadaw (2,000 MMK)</option>
                            <option value="Latha">Latha (2,000 MMK)</option>
                            <option value="Pabedan">Pabedan (2,000 MMK)</option>
                            <option value="Kyauktada">Kyauktada (2,000 MMK)</option>
                            <option value="Botataung">Botataung (2,500 MMK)</option>
                            <option value="Pazundaung">Pazundaung (2,500 MMK)</option>
                            <option value="Mingala Taungnyunt">Mingala Taungnyunt (2,500 MMK)</option>
                            <option value="Thaketa">Thaketa (3,000 MMK)</option>
                            <option value="North Okkalapa">North Okkalapa (3,000 MMK)</option>
                            <option value="South Okkalapa">South Okkalapa (3,000 MMK)</option>
                            <option value="Thingangyun">Thingangyun (2,500 MMK)</option>
                            <option value="Yankin">Yankin (2,500 MMK)</option>
                            <option value="Tamwe">Tamwe (2,500 MMK)</option>
                            <option value="Sanchaung">Sanchaung (2,000 MMK)</option>
                            <option value="Kyimyindaing">Kyimyindaing (2,000 MMK)</option>
                            <option value="Ahlon">Ahlon (2,500 MMK)</option>
                            <option value="Dala">Dala (4,000 MMK)</option>
                            <option value="Seikkan">Seikkan (4,000 MMK)</option>
                            <option value="Thongwa">Thongwa (6,000 MMK)</option>
                            <option value="Kayan">Kayan (6,000 MMK)</option>
                            <option value="Twante">Twante (6,000 MMK)</option>
                            <option value="Kawhmu">Kawhmu (7,000 MMK)</option>
                            <option value="Kungyangon">Kungyangon (8,000 MMK)</option>
                            <option value="Dagon Seikkan">Dagon Seikkan (4,000 MMK)</option>
                            <option value="North Dagon">North Dagon (3,500 MMK)</option>
                            <option value="East Dagon">East Dagon (3,500 MMK)</option>
                            <option value="South Dagon">South Dagon (3,500 MMK)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="delivery_address">Delivery Address *</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" placeholder="Building name, street, landmark..."></textarea>
                    </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label for="notes">Order Notes (Optional)</label>
                            <textarea id="notes" name="notes" rows="2" placeholder="Any special instructions..."></textarea>
                        </div>
                        <button type="submit" class="submit-btn" id="submitBtn">✓ Place Order</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const deliveryFees = {
            'Hlaing': 2000, 'Kamayut': 2000, 'Mayangone': 2500, 'Insein': 3000,
            'Mingaladon': 3500, 'Shwepyitha': 4000, 'Hlegu': 5000, 'Hmawbi': 5000,
            'Htantabin': 5500, 'Taikkyi': 6000, 'Bahan': 2000, 'Dagon': 2500,
            'Lanmadaw': 2000, 'Latha': 2000, 'Pabedan': 2000, 'Kyauktada': 2000,
            'Botataung': 2500, 'Pazundaung': 2500, 'Mingala Taungnyunt': 2500,
            'Thaketa': 3000, 'North Okkalapa': 3000, 'South Okkalapa': 3000,
            'Thingangyun': 2500, 'Yankin': 2500, 'Tamwe': 2500, 'Sanchaung': 2000,
            'Kyimyindaing': 2000, 'Ahlon': 2500, 'Dala': 4000, 'Seikkan': 4000,
            'Thongwa': 6000, 'Kayan': 6000, 'Twante': 6000, 'Kawhmu': 7000,
            'Kungyangon': 8000, 'Dagon Seikkan': 4000, 'North Dagon': 3500,
            'East Dagon': 3500, 'South Dagon': 3500
        };
        
        const subtotal = <?= $total ?>;
        
        function toggleDeliveryFields() {
            const orderType = document.querySelector('input[name="order_type"]:checked').value;
            const deliverySection = document.getElementById('deliverySection');
            const townshipSelect = document.getElementById('delivery_township');
            const deliveryAddress = document.getElementById('delivery_address');
            const deliveryFeeRow = document.getElementById('deliveryFeeRow');
            const totalAmount = document.getElementById('totalAmount');
            
            if (orderType === 'dine-in') {
                // Hide delivery section for dine-in
                deliverySection.style.display = 'none';
                if (deliveryFeeRow) deliveryFeeRow.style.display = 'none';
                
                // Remove required attributes
                townshipSelect.removeAttribute('required');
                deliveryAddress.removeAttribute('required');
                
                // Update total (no delivery fee)
                if (totalAmount) totalAmount.textContent = subtotal.toLocaleString() + ' MMK';
            } else {
                // Show delivery section for delivery
                deliverySection.style.display = 'block';
                
                // Add required attributes
                townshipSelect.setAttribute('required', 'required');
                deliveryAddress.setAttribute('required', 'required');
                
                // Recalculate delivery fee if township is already selected
                if (townshipSelect.value) {
                    updateDeliveryFee();
                }
            }
        }
        
        function updateDeliveryFee() {
            const township = document.getElementById('delivery_township').value;
            const orderType = document.querySelector('input[name="order_type"]:checked').value;
            const deliveryFeeRow = document.getElementById('deliveryFeeRow');
            const deliveryFeeAmount = document.getElementById('deliveryFeeAmount');
            const totalAmount = document.getElementById('totalAmount');
            
            if (orderType === 'delivery' && township && deliveryFees[township]) {
                const fee = deliveryFees[township];
                const total = subtotal + fee;
                
                if (deliveryFeeRow) deliveryFeeRow.style.display = 'flex';
                if (deliveryFeeAmount) deliveryFeeAmount.textContent = fee.toLocaleString() + ' MMK';
                if (totalAmount) totalAmount.textContent = total.toLocaleString() + ' MMK';
            } else {
                if (deliveryFeeRow) deliveryFeeRow.style.display = 'none';
                if (totalAmount) totalAmount.textContent = subtotal.toLocaleString() + ' MMK';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set up township change listener
            const townshipSelect = document.getElementById('delivery_township');
            if (townshipSelect) {
                townshipSelect.addEventListener('change', updateDeliveryFee);
            }
            
            // Initialize delivery fields based on default selection
            toggleDeliveryFields();
        });
        
        // Prevent multiple form submissions
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            
            // Disable the button and change text
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Processing...';
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
            
            // Re-enable after 5 seconds as a fallback (in case of errors)
            setTimeout(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '✓ Place Order';
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }, 5000);
        });
    </script>
    </script>
</body>
</html>
