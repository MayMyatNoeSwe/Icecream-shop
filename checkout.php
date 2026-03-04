<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
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
    
    // Calculate original total for the subtotal display
    if (isset($item['original_price']) && $item['original_price'] > $item['price']) {
        $originalItemTotal = $item['original_price'] * $item['cart_quantity'];
        $originalTotal += $originalItemTotal;
        $totalSavings += ($originalItemTotal - $itemTotal);
        $hasDiscounts = true;
    } else {
        $originalTotal += $itemTotal;
    }
}

// Apply Cart-Wide Discount from Session Coupon
$cartDiscount = 0;
if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']) {
    if (($_SESSION['discount_type'] ?? '') === 'percentage') {
        $cartDiscount = $total * (($_SESSION['discount_value'] ?? 0) / 100);
    } else {
        $cartDiscount = ($_SESSION['discount_value'] ?? 0);
    }
    $totalSavings += $cartDiscount;
    $total -= $cartDiscount; // Reduce the total to be paid
}

$error = '';
$success = false;

// Get logged-in user's information
$loggedInUser = null;
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
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
        $orderType = 'dine-in'; // Restricted to dine-in/pickup only
        $tableNumber = trim($_POST['table_number'] ?? '');
        $deliveryAddress = '';
        $deliveryTownship = '';
        $notes = trim($_POST['notes'] ?? '');
    
    $deliveryFee = 0;
    $totalWithDelivery = $total;
    
    // Validation: Only basic info needed
    if (empty($name) || empty($email) || empty($paymentMethod)) {
        $error = 'Please fill in all required fields';
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
            
            // Create or get user
            $userId = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO users (id, name, email, phone, role) VALUES (?, ?, ?, ?, 'customer') 
                                 ON DUPLICATE KEY UPDATE id=id");
            $stmt->execute([$userId, $name, $email, $phone]);
            
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            $userId = $user['id'];
            
            // Create order with discount information
            $orderId = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO orders (id, user_id, total_price, original_subtotal, discount_amount, discount_percentage, payment_method, order_type, table_number, delivery_address, delivery_township, delivery_fee, phone, notes, coupon_code) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $discountPercentage = $originalTotal > 0 ? (($totalSavings / $originalTotal) * 100) : 0;
            
            // Allow for a slightly higher discount percent if coupon + usage (since originalTotal doesn't change with coupon, but savings do)
            // Actually, keep it simple. Total Savings / Original Total is fine.
            
            $couponCodeUsed = isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied'] ? $_SESSION['coupon_code'] : null;
            $stmt->execute([$orderId, $userId, $totalWithDelivery, $originalTotal, $totalSavings, $discountPercentage, $paymentMethod, $orderType, $tableNumber, $deliveryAddress, $deliveryTownship, $deliveryFee, $phone, $notes, $couponCodeUsed]);
            
            // Record coupon usage IF an order was successfully created and a coupon was applied
            if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']) {
                $usageStmt = $db->prepare("INSERT INTO coupon_usage (coupon_id, user_id, order_id) VALUES (?, ?, ?)");
                $usageStmt->execute([$_SESSION['coupon_id'], $userId, $orderId]);
            }
            
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
            unset($_SESSION['coupon_id']);
            unset($_SESSION['coupon_code']);
            unset($_SESSION['discount_type']);
            unset($_SESSION['discount_value']);
            
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
    <title>Checkout - Scoops Creamery</title>
    <link rel="icon" type="image/png" href="images/logo-removebg-preview.png">
    <link href="https://fonts.googleapis.com/css2?family=Boogaloo&family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Slabo+27px&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-color: #f1efe9;
            --primary-text: #2c296d;
            --accent-color: #6c5dfc;
            --secondary-text: #6b6b8d;
            --white: #ffffff;
            --btn-bg: #2c296d;
            --nav-height: 75px;
            --transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            --card-bg: rgba(255, 255, 255, 0.4);
            --card-border: rgba(255, 255, 255, 0.3);
            --nav-bg: rgba(241, 239, 233, 0.8);
            --hero-bg: #f1efe9;
            --nav-scrolled-bg: rgba(255, 255, 255, 0.85);
        }

        [data-theme="dark"] {
            --bg-color: #1a1914;
            --primary-text: #f0f0f5;
            --accent-color: #a78bfa;
            --secondary-text: #c4c4d9;
            --white: #1e1e2f;
            --btn-bg: #7c3aed;
            --card-bg: rgba(30, 30, 47, 0.7);
            --card-border: rgba(167, 139, 250, 0.2);
            --nav-bg: rgba(26, 25, 20, 0.95);
            --hero-bg: #1a1914;
            --nav-scrolled-bg: rgba(26, 25, 20, 0.92);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-color);
            color: var(--primary-text);
            min-height: 100vh;
            padding-top: var(--nav-height);
            line-height: 1.6;
        }

        .container { max-width: 1250px; margin: 0 auto; padding: 20px 20px 40px 20px; }
        
        .header-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            text-decoration: none;
        }

        .nav-tabs {
            display: flex;
            gap: 8px;
        }
        
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 30px;
            align-items: start;
        }
        
        .checkout-main {
            background: var(--white);
            border-radius: 28px;
            padding: 20px 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--card-border);
        }
        
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--primary-text);
            margin-bottom: 4px;
            font-weight: 900;
        }
        
        .page-subtitle {
            font-size: 0.9rem;
            color: var(--secondary-text);
            margin-bottom: 15px;
            font-family: 'Slabo 27px', serif;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary-text);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.06);
            background: rgba(0,0,0,0.02);
        }
        
        .back-button:hover {
            background: rgba(0,0,0,0.04);
            transform: translateX(-5px);
            color: var(--accent-color);
        }
        
        .form-section {
            margin-bottom: 15px;
            padding-bottom: 5px;
        }
        
        .section-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary-text);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Slabo 27px', serif;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 18px;
            background: var(--accent-color);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(108, 93, 252, 0.3);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 15px;
        }
        
        .form-grid .full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--primary-text);
        }
        
        input, select, textarea { 
            width: 100%; 
            padding: 8px 12px; 
            border: 2px solid rgba(0,0,0,0.06);
            border-radius: 10px;
            font-size: 0.85rem;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1);
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: rgba(0,0,0,0.01);
            color: var(--primary-text);
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(108, 93, 252, 0.1);
        }
        
        input:read-only {
            background: rgba(0,0,0,0.04);
            cursor: not-allowed;
            color: var(--secondary-text);
            border-color: transparent;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        small {
            display: block;
            margin-top: 8px;
            color: var(--secondary-text);
            font-size: 0.85rem;
            font-family: 'Slabo 27px', serif;
        }

        
        .payment-methods {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .payment-option {
            cursor: pointer;
            position: relative;
        }
        
        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .payment-card {
            background: #f8f9fc;
            border: 2px solid rgba(108, 93, 252, 0.15);
            border-radius: 10px;
            padding: 8px;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            height: 65px;
            min-width: 75px;
            color: var(--primary-text);
        }
        
        .payment-option input[type="radio"]:checked + .payment-card {
            border-color: var(--accent-color);
            background: rgba(108, 93, 252, 0.08);
            box-shadow: 0 4px 12px rgba(108, 93, 252, 0.1);
        }
        
        .payment-card:hover {
            border-color: var(--accent-color);
        }
        
        .payment-icon i {
            font-size: 24px;
            color: var(--accent-color);
            transition: all 0.3s ease;
        }
        
        .payment-option input[type="radio"]:checked + .payment-card .payment-icon i {
            transform: scale(1.1);
        }
        
        .payment-name {
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .payment-select-btn {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid rgba(0,0,0,0.06);
            border-radius: 10px;
            font-size: 0.85rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: rgba(0,0,0,0.01);
            color: var(--primary-text);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            text-align: left;
            height: 38px;
            font-weight: 700;
        }
        
        .payment-select-btn:hover {
            border-color: var(--accent-color);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(108, 93, 252, 0.1);
        }
        
        /* Media Queries */
        @media (max-width: 1024px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
            .order-summary {
                margin-top: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .checkout-main {
                padding: 30px 20px;
            }
            .page-title {
                font-size: 2rem;
            }
            .summary-row.savings {
                padding: 10px 14px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        
        .submit-btn { 
            background: var(--accent-color);
            color: white; 
            border: none; 
            padding: 14px 20px; 
            border-radius: 12px; 
            cursor: pointer; 
            font-size: 1.05rem;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 5px;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .submit-btn:hover { 
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none;
        }
        
        .error { 
            background: #fee2e2;
            color: #991b1b;
            padding: 16px 20px; 
            border-radius: 12px; 
            margin-bottom: 25px;
            font-weight: 600;
            border: 1px solid #fca5a5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error a {
            color: #991b1b;
            font-weight: 700;
            text-decoration: underline;
        }
        
        .success { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 40px; 
            border-radius: 16px; 
            text-align: center;
        }
        
        .success h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .success a { 
            color: white;
            font-weight: 600;
            text-decoration: underline;
        }
        
        #deliverySection {
            transition: all 0.3s ease;
        }
        
        @media (max-width: 1024px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
            .order-summary {
                margin-top: 25px;
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .checkout-main {
                padding: 25px;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
            
            .order-type-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="success">
                <h2>✓ Order Placed Successfully!</h2>
                <p>Thank you for your order. You will receive a confirmation email shortly.</p>
                <p><a href="orders.php">View My Orders</a> | <a href="index.php">Continue Shopping</a></p>
            </div>
        <?php else: ?>
            <div class="checkout-layout">
                <!-- Main Checkout Form -->
                <div class="checkout-main">
                    <h1 class="page-title">Checkout</h1>
                    <p class="page-subtitle">Complete your order and enjoy delicious ice cream!</p>
                    
                    <a href="cart.php" class="back-button">
                        ← Back to Cart
                    </a>
                    
                    <?php if ($error): ?>
                        <div class="error">
                            ⚠️ <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    
                    <form method="POST" id="checkoutForm">
                        <input type="hidden" name="submission_token" value="<?= $_SESSION['checkout_token'] ?>">
                        
                        <div class="form-section">
                            <h2 class="section-title">Customer Information</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?= htmlspecialchars($loggedInUser['name'] ?? $_SESSION['user_name'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email * (Account Email)</label>
                                    <input type="email" id="email" name="email" required readonly
                                           value="<?= htmlspecialchars($_SESSION['user_email']) ?>" style="color: var(--secondary-text);">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone *</label>
                                    <input type="tel" id="phone" name="phone" required placeholder="09xxxxxxxxx"
                                           value="<?= htmlspecialchars($loggedInUser['phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Payment Method *</label>
                                    <button type="button" id="paymentBtn" class="payment-select-btn" onclick="openPaymentModal()">
                                        <i class="bi bi-credit-card-2-front" style="color: var(--accent-color);"></i> <span id="paymentBtnText">KPay</span>
                                    </button>
                                    <input type="hidden" name="payment_method" id="selectedPayment" value="kpay" required>
                                </div>
                                <div class="form-group">
                                    <label for="table_number">Table Number * (Dine-in Only)</label>
                                    <select id="table_number" name="table_number" required>
                                        <option value="">Select Table</option>
                                        <?php for($i=1; $i<=20; $i++): ?>
                                            <option value="Table <?= $i ?>">Table <?= $i ?></option>
                                        <?php endfor; ?>
                                        <option value="Takeaway">Takeaway (Pick up at counter)</option>
                                    </select>
                                </div>
                                
                                <!-- Cash Payment Details Box -->
                                <div id="cashPaymentBox" class="full-width" style="display: none; background: rgba(108, 93, 252, 0.05); padding: 20px; border-radius: 18px; margin-top: 10px; border: 1px dashed var(--accent-color);">
                                    <h3 style="font-size: 0.95rem; margin-bottom: 15px; color: var(--accent-color); font-family: 'Slabo 27px', serif; font-weight: 800;">
                                        <i class="bi bi-cash-stack me-2"></i> Cash Payment Details
                                    </h3>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Net Amount (MMK)</label>
                                            <input type="text" id="netAmount" value="<?= number_format($total, 0, '.', '') ?>" readonly style="font-weight: 800; color: var(--accent-color); font-size: 1rem;">
                                        </div>
                                        <div class="form-group">
                                            <label for="paidAmount">Paid Amount (MMK) *</label>
                                            <input type="number" id="paidAmount" name="paid_amount" placeholder="Enter amount paid" oninput="calculateChange()">
                                        </div>
                                        <div class="form-group full-width">
                                            <label>Change (MMK)</label>
                                            <input type="text" id="changeAmount" value="0" readonly style="font-weight: 800; color: #10b981; font-size: 1.1rem; background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2);">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="notes">Order Notes (Optional)</label>
                            <textarea id="notes" name="notes" rows="2" placeholder="Any special instructions..."></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn" id="submitBtn">✓ Place Order</button>
                    </form>
                </div>
                
                <!-- Order Summary Sidebar -->
                
                <!-- Order Summary Sidebar -->
                <div class="order-summary" style="background: var(--white); border-radius: 28px; padding: 25px 30px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04); border: 1px solid var(--card-border); align-self: start; font-family: 'Plus Jakarta Sans', sans-serif;">
                    <h2 style="font-size: 1.6rem; color: var(--primary-text); font-weight: 700; margin-bottom: 15px; letter-spacing: -0.5px;">Order Summary</h2>
                    
                    <?php foreach ($cart as $item): ?>
                    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 10px;">
                        <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/placeholder.png') ?>" 
                             style="width: 55px; height: 55px; border-radius: 12px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
                        <div style="font-size: 0.95rem; color: var(--primary-text);">
                            <?= htmlspecialchars($item['name']) ?> × <?= $item['cart_quantity'] ?>
                        </div>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 4px; margin-bottom: 15px; font-size: 1rem; color: var(--primary-text);">
                        <?php if (isset($item['has_discount']) && $item['has_discount'] && isset($item['original_price'])): ?>
                            <div style="opacity: 0.9;">
                                <?= number_format($item['original_price'] * $item['cart_quantity'], 0) ?> MMK
                            </div>
                            <div>
                                <?= number_format($item['price'] * $item['cart_quantity'], 0) ?> MMK
                            </div>
                        <?php else: ?>
                            <div><?= number_format($item['price'] * $item['cart_quantity'], 0) ?> MMK</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']): ?>
                    <div style="font-size: 0.95rem; color: var(--accent-color); margin-bottom: 10px; font-weight: 600;">
                        <i class="bi bi-tag-fill me-1"></i> Coupon: <?= htmlspecialchars($_SESSION['coupon_code']) ?> (-<?= number_format($cartDiscount, 0) ?> MMK)
                    </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; flex-direction: column; gap: 4px; font-size: 1rem; color: var(--primary-text); margin-top: 5px;">
                        <div>Subtotal <?= number_format($total + $cartDiscount, 0) ?> MMK</div>
                        <?php if ($cartDiscount > 0): ?>
                            <div>Coupon Discount -<?= number_format($cartDiscount, 0) ?> MMK</div>
                        <?php endif; ?>
                        <?php if ($totalSavings > 0): ?>
                            <div>Total Savings -<?= number_format($totalSavings, 0) ?> MMK</div>
                        <?php endif; ?>
                        <div>Total <?= number_format($total, 0) ?> MMK</div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
    
    <script>
        function openPaymentModal() {
            Swal.fire({
                title: '<span style="font-family:\'Slabo 27px\',serif; font-size:1.3rem;">Select Payment Method</span>',
                html: `
                    <div class="payment-methods" style="justify-content: center; margin-top: 15px;">
                        <label class="payment-option" onclick="selectSwalPayment('kpay')">
                            <div class="payment-card">
                                <div class="payment-icon"><i class="bi bi-credit-card-2-front"></i></div>
                                <div class="payment-name">KPay</div>
                            </div>
                        </label>
                        <label class="payment-option" onclick="selectSwalPayment('wavepay')">
                            <div class="payment-card">
                                <div class="payment-icon"><i class="bi bi-phone"></i></div>
                                <div class="payment-name">WavePay</div>
                            </div>
                        </label>
                        <label class="payment-option" onclick="selectSwalPayment('cash')">
                            <div class="payment-card">
                                <div class="payment-icon"><i class="bi bi-cash"></i></div>
                                <div class="payment-name">Cash</div>
                            </div>
                        </label>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
                background: document.documentElement.getAttribute('data-theme') === 'dark' ? '#1e1e2f' : '#ffffff',
                color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#f0f0f5' : '#2c296d',
            });
        }
        
        function selectSwalPayment(method) {
            document.getElementById('selectedPayment').value = method;
            const btnText = document.getElementById('paymentBtnText');
            const icon = document.querySelector('#paymentBtn i');
            
            if (method === 'kpay') {
                btnText.textContent = 'KPay';
                icon.className = 'bi bi-credit-card-2-front';
                
                // Show KPay QR Code Modal
                Swal.fire({
                    title: '<span style="font-family:\'Plus Jakarta Sans\',sans-serif; font-weight:800; font-size:1.5rem; color:#2c296d;">Secure Payment</span>',
                    html: `
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 15px; padding: 5px 0;">
                            <div style="background: linear-gradient(145deg, #1059b0, #003a8c); padding: 20px; border-radius: 28px; width: 100%; box-shadow: 0 15px 35px rgba(0, 58, 140, 0.15); border: 1px solid rgba(255,255,255,0.1);">
                                <div style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 500; text-align: center; margin-bottom: 15px; font-family: 'Plus Jakarta Sans', sans-serif;">
                                    မိမိထံ ငွေပေးချေရန် KBZPay QR Scanner ကို အသုံးပြုပါ။
                                </div>
                                
                                <div style="background: white; padding: 12px; border-radius: 20px; margin: 0 auto; width: fit-content; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative;">
                                    <div style="position: absolute; top: -8px; right: -8px; background: #ffdf00; color: #000; padding: 3px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">OFFICIAL</div>
                                    <img src="images/payment/kpay_qr.png" style="width: 170px; height: 170px; object-fit: contain; border-radius: 12px;" alt="KPay QR Code">
                                </div>

                                <div style="margin-top: 15px; background: rgba(255,255,255,0.1); padding: 12px 18px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);">
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800; margin-bottom: 3px;">ACCOUNT NAME</div>
                                    <div style="color: white; font-weight: 800; font-size: 1rem; letter-spacing: 0.5px;">DAW MAY MYAT NOE SWE</div>
                                    
                                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                                        <div style="color: rgba(255,255,255,0.9); font-weight: 700; font-size: 0.95rem; letter-spacing: 1px;">*******9229</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="font-size: 0.8rem; color: #6b6b8d; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                <i class="bi bi-shield-check" style="color: #10b981; font-size: 1rem;"></i> Encrypted & Secured Trace
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Done Selection',
                    confirmButtonColor: '#6c5dfc',
                    confirmButtonClass: 'premium-btn',
                    background: '#ffffff',
                    padding: '1.5rem',
                    width: '420px',
                    showCloseButton: true,
                    customClass: {
                        confirmButton: 'premium-confirm-btn'
                    }
                });
                return;
            } else if (method === 'wavepay') {
                btnText.textContent = 'WavePay';
                icon.className = 'bi bi-phone';

                // Show WavePay QR Code Modal
                Swal.fire({
                    title: '<span style="font-family:\'Plus Jakarta Sans\',sans-serif; font-weight:800; font-size:1.5rem; color:#2c296d;">Secure Payment</span>',
                    html: `
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 15px; padding: 5px 0;">
                            <div style="background: linear-gradient(135deg, #f7941d, #e21c23); padding: 20px; border-radius: 28px; width: 100%; box-shadow: 0 15px 35px rgba(226, 28, 35, 0.15); border: 1px solid rgba(255,255,255,0.1);">
                                <div style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 500; text-align: center; margin-bottom: 15px; font-family: 'Plus Jakarta Sans', sans-serif;">
                                    မိမိထံ ငွေပေးချေရန် WavePay QR Scanner ကို အသုံးပြုပါ။
                                </div>
                                
                                <div style="background: white; padding: 12px; border-radius: 20px; margin: 0 auto; width: fit-content; box-shadow: 0 10px 25px rgba(0,0,0,0.1); position: relative;">
                                    <div style="position: absolute; top: -8px; right: -8px; background: #ffdf00; color: #000; padding: 3px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">OFFICIAL</div>
                                    <img src="images/payment/wavepay_qr.png" style="width: 170px; height: 170px; object-fit: contain; border-radius: 12px;" alt="WavePay QR Code">
                                </div>

                                <div style="margin-top: 15px; background: rgba(255,255,255,0.1); padding: 12px 18px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);">
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800; margin-bottom: 3px;">ACCOUNT NAME</div>
                                    <div style="color: white; font-weight: 800; font-size: 1rem; letter-spacing: 0.5px;">DAW MAY MYAT NOE SWE</div>
                                    
                                    <div style="display: flex; justify-content: center; align-items: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                                        <div style="color: rgba(255,255,255,0.9); font-weight: 700; font-size: 0.95rem; letter-spacing: 1px;">*******9229</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="font-size: 0.8rem; color: #6b6b8d; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                                <i class="bi bi-shield-check" style="color: #10b981; font-size: 1rem;"></i> Encrypted & Secured Trace
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Done Selection',
                    confirmButtonColor: '#6c5dfc',
                    background: '#ffffff',
                    padding: '1.5rem',
                    width: '420px',
                    showCloseButton: true,
                    customClass: {
                        confirmButton: 'premium-confirm-btn'
                    }
                });
                return;
            } else if (method === 'cash') {
                btnText.textContent = 'Cash';
                icon.className = 'bi bi-cash';
                document.getElementById('cashPaymentBox').style.display = 'block';
                document.getElementById('paidAmount').required = true;
            } else {
                document.getElementById('cashPaymentBox').style.display = 'none';
                document.getElementById('paidAmount').required = false;
            }
            
            Swal.close();
        }

        function calculateChange() {
            const netAmount = parseFloat(document.getElementById('netAmount').value) || 0;
            const paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;
            const changeAmountField = document.getElementById('changeAmount');
            
            if (paidAmount >= netAmount) {
                const change = paidAmount - netAmount;
                changeAmountField.value = change.toLocaleString() + ' MMK';
                changeAmountField.style.color = '#10b981';
            } else if (paidAmount > 0) {
                changeAmountField.value = 'Insufficient';
                changeAmountField.style.color = '#ef4444';
            } else {
                changeAmountField.value = '0 MMK';
                changeAmountField.style.color = '#10b981';
            }
        }

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
    <?php include 'footer.php'; ?>
</body>
</html>
