<?php
session_start();
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
    unset($_SESSION['coupon_applied'], $_SESSION['coupon_code'], $_SESSION['cart_discount']);
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

$cartDiscount = (isset($_SESSION['coupon_applied']) && $_SESSION['coupon_applied']) ? $total * 0.10 : 0;
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/cart.css">
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
            
            <div class="row g-4">
                <div class="col-lg-8">
                    <?php foreach ($cart as $index => $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/placeholder.png') ?>" class="item-img" alt="Product">
                                </div>
                                <div class="col item-details">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                        <div class="text-end">
                                            <div style="font-size: 1.1rem; font-weight: 800; font-family: 'Playfair Display';">
                                                <?= number_format($item['price'] * $item['cart_quantity'], 0) ?> <span style="font-size: 0.7rem; font-family: sans-serif;">MMK</span>
                                            </div>
                                            <?php if (isset($item['original_price']) && $item['original_price'] > $item['price']): ?>
                                                <div class="text-muted" style="text-decoration: line-through; font-size: 0.75rem;">
                                                    <?= number_format($item['original_price'] * $item['cart_quantity'], 0) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="item-meta">
                                        <?php if (isset($item['size_name'])): ?>
                                            <span class="meta-badge"><?= $item['size_name'] ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['toppings_details'])): ?>
                                            <?php foreach ($item['toppings_details'] as $t): ?>
                                                <span class="meta-badge">+ <?= $t['name'] ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mt-3 gap-3">
                                        <div class="qty-control">
                                            <form method="POST" action="update_cart.php" class="m-0">
                                                <input type="hidden" name="index" value="<?= $index ?>"><input type="hidden" name="action" value="decrease">
                                                <button type="submit" class="qty-btn"><i class="bi bi-dash-lg"></i></button>
                                            </form>
                                            <span class="qty-val"><?= $item['cart_quantity'] ?></span>
                                            <form method="POST" action="update_cart.php" class="m-0">
                                                <input type="hidden" name="index" value="<?= $index ?>"><input type="hidden" name="action" value="increase">
                                                <button type="submit" class="qty-btn"><i class="bi bi-plus-lg"></i></button>
                                            </form>
                                        </div>
                                        <form method="POST" action="update_cart.php" class="m-0">
                                            <input type="hidden" name="index" value="<?= $index ?>"><input type="hidden" name="action" value="remove">
                                            <button type="submit" class="btn-remove"><i class="bi bi-trash3 me-1"></i> Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <a href="index.php" class="text-decoration-none mt-4 d-inline-block" style="color:var(--secondary-text); font-weight:700;">
                        <i class="bi bi-arrow-left me-2"></i> Continue Shopping
                    </a>
                </div>

                <div class="col-lg-4">
                    <div class="summary-card">
                        <h2 class="summary-title">Summary</h2>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?= number_format($total, 0) ?> MMK</span>
                        </div>
                        <?php if ($totalSavings > 0): ?>
                            <div class="summary-row" style="color: #4ade80; opacity: 1;">
                                <span>Total Savings</span>
                                <span>-<?= number_format($totalSavings, 0) ?> MMK</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['coupon_applied'])): ?>
                            <div class="summary-row" style="color: var(--accent-color); opacity: 1; font-weight: 700;">
                                <span>Promo Discount (10%)</span>
                                <span>-<?= number_format($cartDiscount, 0) ?> MMK</span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-total">
                            <span>Total</span>
                            <span><?= number_format($finalTotal, 0) ?></span>
                        </div>

                        <a href="checkout.php" class="btn-checkout">Checkout Now</a>
                        
                        <div class="mt-4 pt-4 border-top border-secondary">
                            <form method="POST">
                                <label class="mb-2 d-block" style="font-size: 0.8rem; opacity: 0.7;">PROMO CODE</label>
                                <div class="d-flex gap-2">
                                    <input type="text" name="coupon_code" class="promo-input" placeholder="Enter code">
                                    <button type="submit" name="apply_coupon" class="btn btn-light rounded-pill px-3">Apply</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
