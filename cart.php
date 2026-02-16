<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('cart.php'));
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Coupon Removal (Check this FIRST)
if ((isset($_POST['remove_coupon']) && $_POST['remove_coupon']) || (isset($_GET['remove_coupon']) && $_GET['remove_coupon'])) {
    $_SESSION['coupon_applied'] = false; // Explicitly turn off first
    unset($_SESSION['coupon_applied']);
    unset($_SESSION['coupon_code']);
    unset($_SESSION['cart_discount']);
    session_write_close();
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'];
$total = 0;
$originalTotal = 0;
$totalSavings = 0;

// Handle Coupon Application
$couponMessage = '';
$couponError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    if ($code === 'SCOOP10') {
        $_SESSION['coupon_applied'] = true;
        $_SESSION['coupon_code'] = 'SCOOP10';
        $couponMessage = "10% Discount Applied!";
    } else {
        $couponError = "Invalid coupon code.";
    }
}

// Calculate totals with discount information
foreach ($cart as $item) {
    $itemTotal = $item['price'] * $item['cart_quantity'];
    $total += $itemTotal;
    
    // Check if item has discount information
    if (isset($item['original_price']) && isset($item['has_discount']) && $item['has_discount'] && $item['original_price'] > $item['price']) {
        $originalItemTotal = $item['original_price'] * $item['cart_quantity'];
        $originalTotal += $originalItemTotal;
        $totalSavings += ($originalItemTotal - $itemTotal);
    } else {
        $originalTotal += $itemTotal;
    }
}

// Apply Cart-Wide Discount
$cartDiscount = 0;
if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']) {
    $cartDiscount = $total * 0.10;
}
$finalTotal = $total - $cartDiscount;

// Store final total in session for checkout (optional, but checkout usually recalculates)
$_SESSION['cart_total'] = $finalTotal;
$_SESSION['cart_discount'] = $cartDiscount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Scoops</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c296d;
            --accent-color: #6c5dfc;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: 1px solid rgba(255, 255, 255, 0.2);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2d3748;
        }
        
        /* Premium Header Styling */
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
            box-shadow: var(--shadow-lg);
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

        /* Cart Container */
        .cart-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: var(--shadow-lg);
            padding: 40px;
            border: var(--glass-border);
            margin-bottom: 40px;
        }
        
        .page-title-group {
            margin-bottom: 40px;
            border-bottom: 1px solid rgba(44, 41, 109, 0.1);
            padding-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-color);
            margin: 0;
        }

        .cart-count {
            background: #6c5dfc;
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
        }

        /* Cart Items */
        .cart-item {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .cart-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .item-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .item-meta {
            color: rgba(102, 126, 234, 0.8);
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        /* Quantity Controls */
        .quantity-wrapper {
            background: #f7fafc;
            border-radius: 50px;
            padding: 5px;
            display: inline-flex;
            align-items: center;
            border: 1px solid #e2e8f0;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: white;
            color: var(--primary-color);
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        .quantity-display {
            min-width: 40px;
            text-align: center;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Prices */
        .price-tag {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--primary-color);
        }
        
        .old-price {
            text-decoration: line-through;
            color: #a0aec0;
            font-size: 1rem;
            margin-right: 10px;
        }
        
        .discount-badge {
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
            color: white;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 50px;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
        }

        /* Buttons */
        .btn-remove {
            color: #e53e3e;
            background: rgba(229, 62, 62, 0.1);
            border: none;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-remove:hover {
            background: #e53e3e;
            color: white;
        }

        /* Summary Section */
        .cart-summary {
            background: linear-gradient(135deg, #2c296d 0%, #2c1b69 100%);
            color: white;
            border-radius: 25px;
            padding: 35px;
            position: relative;
            overflow: hidden;
        }
        
        .cart-summary::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('images/pattern.png'); 
            opacity: 0.1;
            pointer-events: none;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .summary-total {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: 20px;
            padding-top: 20px;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-checkout {
            display: block;
            text-align: center;
            background: white;
            color: var(--primary-color);
            width: 100%;
            padding: 18px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            margin-top: 25px;
            transition: transform 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
        }
        
        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .btn-continue {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: rgba(102, 126, 234, 0.9);
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: color 0.3s;
        }
        
        .btn-continue:hover {
            color: var(--primary-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
        }
        
        .empty-icon {
            font-size: 6rem;
            background: -webkit-linear-gradient(#667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
            opacity: 0.5;
        }
        
        .btn-start-shopping {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
        }
        
        .btn-start-shopping:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        @media (max-width: 768px) {
            .header-glass { flex-direction: column; gap: 20px; }
            .cart-container { padding: 20px; }
            .item-name { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <!-- Premium Header -->
    <header>
        <div class="container">
            <div class="header-glass">
                <a href="index.php" class="header-brand">Scoops Creamery</a>
                <nav class="nav-tabs-premium">
                    <a href="index.php" class="nav-link-premium">Collection</a>
                    <a href="cart.php" class="nav-link-premium active">Cart</a>
                    <a href="orders.php" class="nav-link-premium">My Orders</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="cart-container">
            <?php if (empty($cart)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-cart-x"></i></div>
                    <h2 class="mb-3" style="font-family: 'Playfair Display'; font-weight: 700; color: var(--primary-color);">Your cart is empty</h2>
                    <p class="text-muted" style="font-size: 1.1rem;">Looks like you haven't indulged yet.<br>Discover our premium handcrafted flavors.</p>
                    <a href="index.php" class="btn-start-shopping">
                        Browse Flavors
                    </a>
                </div>
            <?php else: ?>
                <div class="page-title-group">
                    <h1 class="cart-title">Shopping Cart</h1>
                    <span class="cart-count"><?= array_sum(array_column($cart, 'cart_quantity')) ?> Items</span>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Cart Items -->
                        <?php foreach ($cart as $index => $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <?php if (isset($item['description']) && !isset($item['custom'])): ?>
                                        <div class="item-meta"><?= htmlspecialchars($item['description']) ?></div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($item['custom'])): ?>
                                        <div class="item-meta">
                                            <?php if (isset($item['size_name'])): ?>
                                                <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($item['size_name']) ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['toppings_details'])): ?>
                                                <?php foreach($item['toppings_details'] as $topping): ?>
                                                    <span class="badge bg-light text-dark border me-1">+ <?= htmlspecialchars($topping['name']) ?></span>
                                                <?php endforeach; ?>
                                            <?php elseif (!empty($item['toppings']) && is_array($item['toppings'])): ?>
                                                 <!-- Fallback or skip if toppings are just IDs -->
                                                 <?php foreach($item['toppings'] as $topping): ?>
                                                    <?php if (is_array($topping) && isset($topping['name'])): ?>
                                                        <span class="badge bg-light text-dark border me-1">+ <?= htmlspecialchars($topping['name']) ?></span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="update_cart.php" class="mt-3">
                                        <input type="hidden" name="index" value="<?= $index ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn-remove">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-3 text-center my-3 my-md-0">
                                    <div class="quantity-wrapper">
                                        <form method="POST" action="update_cart.php" style="display: inline;">
                                            <input type="hidden" name="index" value="<?= $index ?>">
                                            <input type="hidden" name="action" value="decrease">
                                            <button type="submit" class="quantity-btn"><i class="bi bi-dash"></i></button>
                                        </form>
                                        <span class="quantity-display"><?= $item['cart_quantity'] ?></span>
                                        <form method="POST" action="update_cart.php" style="display: inline;">
                                            <input type="hidden" name="index" value="<?= $index ?>">
                                            <input type="hidden" name="action" value="increase">
                                            <button type="submit" class="quantity-btn"><i class="bi bi-plus"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <?php if (isset($item['original_price']) && isset($item['has_discount']) && $item['has_discount'] && $item['original_price'] > $item['price']): ?>
                                        <div class="d-flex flex-column align-items-end">
                                            <div>
                                                <span class="old-price"><?= number_format($item['original_price'] * $item['cart_quantity'], 0) ?></span>
                                                <span class="discount-badge">Save <?= floor((($item['original_price'] - $item['price']) / $item['original_price']) * 100) ?>%</span>
                                            </div>
                                            <div class="price-tag mt-1">
                                                <?= number_format($item['price'] * $item['cart_quantity'], 0) ?> <span style="font-size: 0.8rem; vertical-align: middle;">MMK</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="price-tag">
                                            <?= number_format($item['price'] * $item['cart_quantity'], 0) ?> <span style="font-size: 0.8rem; vertical-align: middle;">MMK</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <a href="index.php" class="btn-continue">
                            <i class="bi bi-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                    
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <div class="cart-summary">
                            <h3 style="font-family: 'Playfair Display'; margin-bottom: 25px;">Order Summary</h3>
                            
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span><?= number_format($total, 0) ?> MMK</span>
                            </div>
                            
                            <?php if ($totalSavings > 0): ?>
                            <div class="summary-row" style="color: #48bb78;">
                                <span>Item Savings</span>
                                <span>-<?= number_format($totalSavings, 0) ?> MMK</span>
                            </div>
                            <?php endif; ?>

                            <!-- Coupon Section -->
                            <?php if (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']): ?>
                                <div class="summary-row" style="color: #6c5dfc; font-weight: 600;">
                                    <span>Discount (10%)</span>
                                    <span>-<?= number_format($cartDiscount, 0) ?> MMK</span>
                                </div>
                                <div style="margin-bottom: 15px; font-size: 0.9rem;">
                                    <span class="badge bg-success">SCOOP10 Applied</span>
                                    <form method="POST" action="cart.php" style="display:inline;">
                                        <button type="submit" name="remove_coupon" value="1" class="btn btn-sm" style="background: rgba(220, 53, 69, 0.2); color: #ff8787; border: 1px solid rgba(220, 53, 69, 0.5); padding: 2px 12px; font-size: 0.85rem; border-radius: 20px; margin-left: 10px; transition: all 0.2s;">
                                            <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" name="coupon_code" class="form-control" placeholder="Promo Code" style="background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 10px 0 0 10px; padding: 10px;">
                                        <button type="submit" name="apply_coupon" class="btn btn-light" style="border-radius: 0 10px 10px 0;">Apply</button>
                                    </div>
                                    <?php if ($couponError): ?>
                                        <div class="text-danger mt-1" style="font-size: 0.85rem;"><?= $couponError ?></div>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                            
                            <div class="summary-row">
                                <span>Delivery</span>
                                <span style="font-size: 0.9rem; opacity: 0.7;">Calculated at checkout</span>
                            </div>
                            
                            <div class="summary-total">
                                <span>Total</span>
                                <span><?= number_format($finalTotal, 0) ?> MMK</span>
                            </div>
                            
                            <a href="checkout.php" class="btn-checkout">
                                Checkout Now <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
