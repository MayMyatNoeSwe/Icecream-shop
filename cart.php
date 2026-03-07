<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('cart.php'));
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Coupon Removal
if (isset($_POST['remove_coupon'])) {
    unset($_SESSION['coupon_applied'], $_SESSION['coupon_id'], $_SESSION['coupon_code'], $_SESSION['discount_type'], $_SESSION['discount_value']);
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'];
$total = 0;
$totalSavings = 0;

foreach ($cart as $item) {
    $itemTotal = $item['price'] * $item['cart_quantity'];
    $total += $itemTotal;
    if (isset($item['original_price']) && $item['original_price'] > $item['price']) {
        $totalSavings += ($item['original_price'] - $item['price']) * $item['cart_quantity'];
    }
}

$couponMessage = '';
$couponStatus = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'custom_updated') {
        $couponMessage = 'Item customizations updated successfully!';
        $couponStatus = 'success';
    } elseif ($_GET['success'] === 'custom_added') {
        $couponMessage = 'Custom ice cream added to your cart!';
        $couponStatus = 'success';
    }
}

if (isset($_GET['reorder'])) {
    if ($_GET['reorder'] === 'success') {
        $couponMessage = 'Items from previous order added to cart!';
        $couponStatus = 'success';
    } elseif ($_GET['reorder'] === 'reorder_partial') {
        $couponMessage = 'Some items were added, but some are currently out of stock.';
        $couponStatus = 'warning';
    }
}

// Handle Coupon Application
if (isset($_POST['apply_coupon'])) {
    $code = strtoupper(trim($_POST['coupon_code']));
    
    if (empty($code)) {
        $couponMessage = 'Please enter a coupon code.';
        $couponStatus = 'error';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
            $stmt->execute([$code]);
            $coupon = $stmt->fetch();

            if (!$coupon) {
                $couponMessage = 'Invalid or expired coupon code.';
                $couponStatus = 'error';
            } else {
                $now = date('Y-m-d H:i:s');
                $usageCheck = $db->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ?");
                $usageCheck->execute([$coupon['id']]);
                $totalUsed = $usageCheck->fetchColumn();

                $userUsageCheck = $db->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
                $userUsageCheck->execute([$coupon['id'], $_SESSION['user_id']]);
                $userUsed = $userUsageCheck->fetchColumn();

                if ($now < $coupon['valid_from'] || $now > $coupon['valid_until']) {
                    $couponMessage = 'This coupon is not valid yet or has expired.';
                    $couponStatus = 'error';
                } elseif ($coupon['max_uses'] !== null && $totalUsed >= $coupon['max_uses']) {
                    $couponMessage = 'This coupon has reached its maximum usage limit.';
                    $couponStatus = 'error';
                } elseif ($userUsed >= $coupon['max_uses_per_user']) {
                    $couponMessage = 'This coupon code has already been redeemed by your account. Each offer is valid for a single use only.';
                    $couponStatus = 'error';
                } elseif ($total < $coupon['min_order_amount']) {
                    $couponMessage = 'Minimum order amount for this coupon is ' . number_format($coupon['min_order_amount']) . ' MMK.';
                    $couponStatus = 'error';
                } else {
                    $_SESSION['coupon_applied'] = true;
                    $_SESSION['coupon_id'] = $coupon['id'];
                    $_SESSION['coupon_code'] = $coupon['code'];
                    $_SESSION['discount_type'] = $coupon['discount_type'];
                    $_SESSION['discount_value'] = $coupon['discount_value'];
                    $_SESSION['coupon_message'] = 'Coupon code \'' . $code . '\' applied successfully!';
                    $_SESSION['coupon_status'] = 'success';
                    header('Location: cart.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['coupon_message'] = 'An error occurred while applying the coupon.';
            $_SESSION['coupon_status'] = 'error';
            header('Location: cart.php');
            exit;
        }
    }
    // For other errors
    $_SESSION['coupon_message'] = $couponMessage;
    $_SESSION['coupon_status'] = $couponStatus;
    header('Location: cart.php');
    exit;
}

// Retrieve message from session if it exists
if (isset($_SESSION['coupon_message'])) {
    $couponMessage = $_SESSION['coupon_message'];
    $couponStatus = $_SESSION['coupon_status'];
    unset($_SESSION['coupon_message'], $_SESSION['coupon_status']);
}

// Handle Reorder Messages
$reorderMessage = '';
$reorderStatus = '';
if (isset($_GET['reorder'])) {
    if ($_GET['reorder'] === 'success') {
        $reorderMessage = 'Items from your previous order have been added to your cart!';
        $reorderStatus = 'success';
    } elseif ($_GET['reorder'] === 'reorder_partial') {
        $reorderMessage = 'Some items were added, but others are currently out of stock or unavailable.';
        $reorderStatus = 'info';
    }
}

// Calculate Discount
$cartDiscount = 0;
if (isset($_SESSION['coupon_applied'])) {
    if ($_SESSION['discount_type'] === 'percentage') {
        $cartDiscount = $total * ($_SESSION['discount_value'] / 100);
    } else {
        $cartDiscount = $_SESSION['discount_value'];
    }
}

$finalTotal = $total - $cartDiscount;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Scoops Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Slabo+27px&family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f1efe9;
            --primary-text: #2c296d;
            --secondary-text: #6b6b8d;
            --accent-color: #6c5dfc;
            --white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.6);
            --card-border: rgba(255, 255, 255, 0.4);
            --nav-bg: rgba(241, 239, 233, 0.8);
            --nav-height: 60px;
            --nav-scrolled-bg: rgba(255, 255, 255, 0.85);
            --transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            --hero-bg: #f1efe9;
        }

        [data-theme="dark"] {
            --bg-color: #1a1914;
            --primary-text: #f0f0f5;
            --secondary-text: #c4c4d9;
            --accent-color: #a78bfa;
            --white: #1e1e2f;
            --card-bg: rgba(30, 30, 47, 0.7);
            --card-border: rgba(167, 139, 250, 0.2);
            --nav-bg: rgba(26, 25, 20, 0.95);
            --nav-scrolled-bg: rgba(26, 25, 20, 0.92);
            --hero-bg: #1a1914;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--primary-text);
            min-height: 100vh;
            transition: background-color 0.4s ease, color 0.4s ease;
        }
        .jakarta{
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .playfair{
            font-family: 'Playfair Display', serif;
        }

        /* Cart Styles */
        .cart-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: calc(var(--nav-height) + 60px) 24px 60px;
            width: 100%;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 30px;
            align-items: start;
        }

        @media (max-width: 768px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }
        }



        .cart-item {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 18px;
            border: 1px solid var(--card-border);
            margin-bottom: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .cart-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .item-img {
            width: 95px;
            height: 95px;
            border-radius: 14px;
            object-fit: cover;
            background: rgba(108, 93, 252, 0.04);
            border: 1px solid var(--card-border);
            padding: 4px;
            transition: transform 0.3s ease;
        }

        [data-theme="dark"] .item-img {
            background: rgba(167, 139, 250, 0.08);
        }

        .cart-item:hover .item-img {
            transform: scale(1.05);
        }

        .btn-edit-item {
            background: rgba(108, 93, 252, 0.1);
            color: var(--accent-color);
            border: none;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit-item:hover {
            background: var(--accent-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-remove {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: none;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-remove:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
        }

        .item-details h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .item-meta {
            font-size: 0.9rem;
            color: var(--secondary-text);
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .meta-badge {
            background: rgba(108, 93, 252, 0.08);
            color: var(--accent-color);
            padding: 3px 10px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.02em;
        }

        /* Controls */
        .qty-control {
            display: flex;
            align-items: center;
            background: var(--white);
            padding: 4px;
            border-radius: 100px;
            border: 1px solid var(--card-border);
            width: fit-content;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--primary-text);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: rgba(108, 93, 252, 0.1);
            color: var(--accent-color);
        }

        .qty-val {
            padding: 0 10px;
            font-weight: 800;
            min-width: 35px;
            text-align: center;
            font-size: 0.9rem;
        }

        .btn-remove {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.08);
            border: none;
            padding: 8px 15px;
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .btn-remove:hover {
            background: #ef4444;
            color: white;
        }

        /* Summary */
        .summary-card {
            background: white;
            color: var(--primary-text);
            border-radius: 22px;
            padding: 24px;
            position: sticky;
            top: calc(var(--nav-height) + 20px);
            box-shadow: 0 15px 30px rgba(44, 41, 109, 0.2);
        }

        [data-theme="dark"] .summary-card {
            background: #1e1e2f;
            border: 1px solid var(--card-border);
        }

        .summary-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .summary-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            text-align: center;
            margin-top: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-checkout:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(108, 93, 252, 0.4);
            color: white;
        }

        /* Promo */
        .promo-input {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(9, 9, 9, 0.65);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--primary-text);
            width: 100%;
            outline: none;
            font-size: 0.9rem;
        }

        .promo-btn {
            background: var(--white);
            color: var(--primary-text);
            border: none;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 700;
            margin-top: 8px;
            width: 100%;
            font-size: 0.9rem;
        }

        /* Empty */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            width: 100%;
        }

        .empty-icon {
            font-size: 5rem;
            color: var(--secondary-text);
            margin-bottom: 25px;
            opacity: 0.3;
        }

        .btn-browse {
            background: var(--primary-text);
            color: white;
            padding: 15px 35px;
            border-radius: 100px;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            margin-top: 20px;
        }

        [data-theme="dark"] .btn-browse {
            background: var(--accent-color);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="cart-wrapper">
        <?php if (empty($cart)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-bag-x"></i></div>
                <h2 style="font-family: 'Playfair Display'; font-weight: 900;">Your cart is empty</h2>
                <p class="text-muted">It seems you haven't added any delicacies yet.</p>
                <a href="index.php" class="btn-browse">Discover Flavors</a>
            </div>
        <?php else: ?>
            
            <h2 class="playfair mb-3">My Cart</h2>
            <div class="cart-grid">
                <div id="cart-items-container">
                    
                    <?php foreach ($cart as $index => $item): ?>
                        <div class="cart-item" data-index="<?= $index ?>">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <?php 
                                        $imageUrl = htmlspecialchars($item['image_url'] ?? 'images/placeholder.png');
                                        if (empty($imageUrl)) $imageUrl = 'images/placeholder.png';
                                    ?>
                                    <img src="<?= $imageUrl ?>" class="item-img" alt="Product" onerror="this.onerror=null;this.style.background='rgba(108,93,252,0.08)';this.style.display='flex';this.src='';this.outerHTML='<div class=\'item-img\' style=\'display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--secondary-text);opacity:0.4;\'><i class=\'bi bi-image\'></i></div>';">
                                </div>
                                <div class="col item-details">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                        <div class="text-end">
                                            <div style="font-size: 1.1rem; font-weight: 800; font-family: 'Playfair Display';">
                                                <span id="item-total-<?= $index ?>"><?= number_format($item['price'] * $item['cart_quantity'], 0) ?></span> <span style="font-size: 0.7rem; font-family: sans-serif;">MMK</span>
                                            </div>
                                            <?php if (isset($item['original_price']) && $item['original_price'] > $item['price']): ?>
                                                <div class="text-muted" style="text-decoration: line-through; font-size: 0.75rem;">
                                                    <span id="item-original-total-<?= $index ?>"><?= number_format($item['original_price'] * $item['cart_quantity'], 0) ?> MMK</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="item-meta">
                                        <?php if (isset($item['size_name'])): ?>
                                            <span class="meta-badge"><?= htmlspecialchars($item['size_name']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['toppings_details'])): ?>
                                            <?php foreach ($item['toppings_details'] as $t): ?>
                                                <span class="meta-badge">+ <?= htmlspecialchars($t['name']) ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mt-3 gap-3">
                                        <div class="qty-control">
                                            <button type="button" class="qty-btn" onclick="updateCart(<?= $index ?>, 'decrease')"><i class="bi bi-dash-lg"></i></button>
                                            <span class="qty-val" id="qty-val-<?= $index ?>"><?= $item['cart_quantity'] ?></span>
                                            <button type="button" class="qty-btn" onclick="updateCart(<?= $index ?>, 'increase')"><i class="bi bi-plus-lg"></i></button>
                                        </div>
                                        
                                        <?php if (isset($item['custom']) && $item['custom'] === true): ?>
                                            <a href="index_custom.php?edit_index=<?= $index ?>" class="btn-edit-item">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn-remove" onclick="updateCart(<?= $index ?>, 'remove')"><i class="bi bi-trash3 me-1"></i> Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <a href="index.php" class="text-decoration-none mt-4 d-inline-block" style="color:var(--secondary-text); font-weight:700;">
                        <i class="bi bi-arrow-left me-2"></i> Continue Shopping
                    </a>
                </div>

                <div>
                    <div class="summary-card">
                        <h2 class="summary-title">Summary</h2>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><span id="summary-total"><?= number_format($total, 0) ?></span> MMK</span>
                        </div>
                        
                        <div class="summary-row" id="summary-savings-row" style="color: #4ade80; opacity: 1; display: <?= $totalSavings > 0 ? 'flex' : 'none' ?>;">
                            <span>Total Savings</span>
                            <span>-<span id="summary-savings"><?= number_format($totalSavings, 0) ?></span> MMK</span>
                        </div>
                        
                        <?php if (isset($_SESSION['coupon_applied'])): ?>
                            <div class="summary-row" style="color: var(--accent-color); opacity: 1; font-weight: 700;">
                                <span>Promo Discount (<?= $_SESSION['discount_type'] === 'percentage' ? (int)$_SESSION['discount_value'].'%' : 'Fixed' ?>)</span>
                                <span>-<span id="summary-discount"><?= number_format($cartDiscount, 0) ?></span> MMK</span>
                            </div>
                            <div class="summary-row" style="color: var(--accent-color); font-size: 0.8rem; margin-top: -10px;">
                                <span>Applied: <strong><?= htmlspecialchars($_SESSION['coupon_code']) ?></strong></span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-total">
                            <span>Total</span>
                            <span id="summary-final"><?= number_format($finalTotal, 0) ?> MMK</span>
                        </div>

                        <div class="promo-section-container mt-4 pt-4 border-top border-secondary">
                            <?php if (isset($_SESSION['coupon_applied'])): ?>
                                <div class="applied-coupon-wrapper mb-3">
                                    <form method="POST">
                                        <button type="submit" name="remove_coupon" class="btn btn-outline-danger btn-sm rounded-pill w-100">Remove Coupon</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="coupon-form-wrapper mb-3">
                                    <form method="POST">
                                        <label class="mb-2 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;font-weight:bold;">
                                            PROMO CODE
                                            <?php if ($couponStatus === 'error'): ?>
                                                <span class="text-danger" style="font-size: 0.7rem;">Invalid Code</span>
                                            <?php endif; ?>
                                        </label>
                                        <div class="d-flex gap-2">
                                            <input type="text" name="coupon_code" class="promo-input" placeholder="Enter code">
                                            <button type="submit" name="apply_coupon" class="btn btn-light rounded-pill px-3">Apply</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            
                            <a href="checkout.php" class="btn-checkout w-100" style="margin-top: 15px !important;">CHECKOUT NOW</a>
                        </div>
                    </div>
                </div>
            </div> <!-- end cart-grid -->
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    
    <!-- SweetAlert2 for Coupon & Cart Alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    async function updateCart(index, action) {
        const formData = new FormData();
        formData.append('index', index);
        formData.append('action', action);
        formData.append('ajax', '1');

        try {
            const response = await fetch('update_cart.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const data = await response.json();
                
                if (data.success) {
                    if (data.isEmpty) {
                        window.location.reload();
                        return;
                    }
                    
                    if (action === 'remove' || data.itemQty === 0) {
                        const itemEl = document.querySelector(`.cart-item[data-index="${index}"]`);
                        if (itemEl) itemEl.remove();
                    } else {
                        const qtyVal = document.getElementById(`qty-val-${index}`);
                        if (qtyVal) qtyVal.textContent = data.itemQty;
                        
                        const itemTotal = document.getElementById(`item-total-${index}`);
                        if (itemTotal) itemTotal.textContent = data.itemTotal;
                        
                        const itemOriginalTotal = document.getElementById(`item-original-total-${index}`);
                        if (itemOriginalTotal) itemOriginalTotal.textContent = data.itemOriginalTotal;
                    }

                    const summaryTotal = document.getElementById('summary-total');
                    if (summaryTotal) summaryTotal.textContent = data.total;

                    const summarySavings = document.getElementById('summary-savings');
                    const summarySavingsRow = document.getElementById('summary-savings-row');
                    if (summarySavings && summarySavingsRow) {
                        summarySavings.textContent = data.totalSavings;
                        if (parseInt(data.totalSavings.replace(/,/g, '')) > 0) {
                            summarySavingsRow.style.setProperty('display', 'flex', 'important');
                        } else {
                            summarySavingsRow.style.setProperty('display', 'none', 'important');
                        }
                    }

                    const summaryDiscount = document.getElementById('summary-discount');
                    if (summaryDiscount) summaryDiscount.textContent = data.cartDiscount;

                    const summaryFinal = document.getElementById('summary-final');
                    if (summaryFinal) summaryFinal.textContent = data.finalTotal;
                    
                    const cartCountBadge = document.querySelector('.cart-count');
                    if (cartCountBadge) cartCountBadge.textContent = data.cartCount;
                }
            } else {
                console.error('Update failed');
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    <?php if ($couponMessage): ?>
        Swal.fire({
            icon: '<?= $couponStatus ?>',
            title: '<?= $couponStatus === "success" ? "Success!" : "Oops..." ?>',
            text: '<?= $couponMessage ?>',
            confirmButtonColor: '<?= $couponStatus === "success" ? "#6c5dfc" : "#ef4444" ?>'
        });
    <?php endif; ?>


    </script>
</body>
</html>
