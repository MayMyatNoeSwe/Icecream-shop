<?php
session_start();
require_once 'config/database.php';

// Require login to view orders
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

// Get orders for the logged-in user only
$orders = [];

try {
    $db = Database::getInstance()->getConnection();
    
    // Get user's email from session
    $userEmail = $_SESSION['user_email'];
    
    $stmt = $db->prepare("SELECT o.*, c.name, c.email 
                         FROM orders o 
                         JOIN customers c ON o.customer_id = c.id 
                         WHERE c.email = ? 
                         ORDER BY o.order_date DESC");
    $stmt->execute([$userEmail]);
    $orders = $stmt->fetchAll();
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Premium Ice Cream</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="cache-buster" content="<?= time() . rand(1000, 9999) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --bg-gradient-start: #f7f3ff;
            --bg-gradient-end: #e8f4ff;
            --primary-text: #2c296d;
            --accent-color: #6c5dfc;
            --secondary-text: #6b6b8d;
            --white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.7);
            --card-border: rgba(255, 255, 255, 0.5);
            --transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        *::-webkit-scrollbar {
            display: none;
        }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            color: var(--primary-text);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(108, 93, 252, 0.1) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(167, 139, 250, 0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        
        .container { 
            max-width: 65%; 
            margin: 0 auto; 
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        @media (max-width: 1024px) {
            .container { max-width: 85%; }
        }
        
        @media (max-width: 768px) {
            .container { max-width: 95%; }
        }
        
        header { 
            padding: 40px 0; 
            margin-bottom: 40px;
        }
        
        .header-glass {
            background: var(--card-bg);
            backdrop-filter: blur(30px);
            border: 1px solid var(--card-border);
            border-radius: 32px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(44, 41, 109, 0.08);
            position: relative;
            overflow: hidden;
        }

        .header-glass::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6c5dfc, #a78bfa, #6c5dfc);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        header h1 { 
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            color: var(--primary-text);
            margin-bottom: 20px;
            font-weight: 900;
            letter-spacing: -0.02em;
            text-align: center;
        }
        
        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(108, 93, 252, 0.08);
            padding: 10px 28px;
            border-radius: 50px;
            color: var(--primary-text);
            font-size: 0.95rem;
            margin-bottom: 35px;
            border: 1px solid rgba(108, 93, 252, 0.15);
            backdrop-filter: blur(10px);
            font-weight: 600;
        }

        .nav-tabs-premium {
            display: inline-flex;
            background: rgba(108, 93, 252, 0.06);
            padding: 8px;
            border-radius: 100px;
            gap: 6px;
            border: 1px solid rgba(108, 93, 252, 0.1);
        }
        
        .nav-link-premium {
            color: var(--secondary-text);
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 700;
            transition: var(--transition);
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link-premium:hover {
            color: var(--accent-color);
            background: rgba(108, 93, 252, 0.08);
        }
        
        .nav-link-premium.active {
            background: var(--white);
            color: var(--accent-color);
            box-shadow: 0 4px 20px rgba(108, 93, 252, 0.15);
        }
        
        .order-card { 
            background: var(--card-bg);
            backdrop-filter: blur(30px);
            padding: 35px; 
            border-radius: 28px; 
            margin-bottom: 30px;
            box-shadow: 0 15px 50px rgba(44, 41, 109, 0.08);
            transition: var(--transition);
            border: 1px solid var(--card-border);
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(108, 93, 252, 0.02) 0%, rgba(167, 139, 250, 0.02) 100%);
            pointer-events: none;
        }
        
        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 70px rgba(44, 41, 109, 0.12);
            border-color: rgba(108, 93, 252, 0.3);
        }
        
        .order-header { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 25px; 
            padding-bottom: 25px; 
            border-bottom: 2px solid rgba(108, 93, 252, 0.08);
            position: relative;
            z-index: 1;
        }
        
        .order-id { 
            color: var(--secondary-text);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .order-date { 
            color: var(--secondary-text);
            font-size: 14px;
            opacity: 0.8;
        }
        
        .order-status { 
            display: inline-block; 
            padding: 10px 24px; 
            border-radius: 50px; 
            font-size: 13px; 
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .order-status-container {
            text-align: right;
        }
        
        .status-explanation {
            font-size: 12px;
            color: var(--secondary-text);
            margin-top: 6px;
            font-weight: 500;
            opacity: 0.8;
        }
        
        .status-pending { 
            background: linear-gradient(135deg, #ffd97d 0%, #ffb84d 100%);
            color: #8b5a00;
            animation: pulse 2s infinite;
            box-shadow: 0 4px 15px rgba(255, 184, 77, 0.3);
        }
        
        .status-completed { 
            background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
            color: #065f46;
            animation: celebrate 0.5s ease-in-out;
            box-shadow: 0 4px 15px rgba(110, 231, 183, 0.3);
        }
        
        .status-cancelled {
            background: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
            color: #991b1b;
            box-shadow: 0 4px 15px rgba(248, 113, 113, 0.3);
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.03); }
        }
        
        @keyframes celebrate {
            0% { transform: scale(1); }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        
        .order-items { 
            margin: 25px 0;
            position: relative;
            z-index: 1;
        }
        
        .order-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 16px 0; 
            border-bottom: 1px solid rgba(108, 93, 252, 0.06);
            color: var(--primary-text);
            align-items: flex-start;
            transition: var(--transition);
        }

        .order-item:hover {
            padding-left: 10px;
            background: rgba(108, 93, 252, 0.02);
            margin: 0 -10px;
            padding-right: 10px;
            border-radius: 12px;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 700;
            color: var(--primary-text);
            margin-bottom: 6px;
            font-size: 15px;
        }
        
        .item-description {
            font-size: 13px;
            color: var(--secondary-text);
            line-height: 1.6;
        }
        
        .item-price {
            font-weight: 700;
            color: var(--accent-color);
            white-space: nowrap;
            margin-left: 20px;
            font-size: 16px;
        }
        
        .order-total { 
            text-align: right; 
            font-size: 28px; 
            font-weight: 900;
            margin-top: 25px;
            font-family: 'Playfair Display', serif;
            position: relative;
            z-index: 1;
        }
        
        .order-total span {
            background: linear-gradient(135deg, #6c5dfc 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .order-actions {
            margin-top: 25px;
            display: flex;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        
        .details-btn {
            background: linear-gradient(135deg, #6c5dfc 0%, #a78bfa 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: var(--transition);
            box-shadow: 0 6px 20px rgba(108, 93, 252, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .details-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(108, 93, 252, 0.4);
        }
        
        .order-details {
            display: none;
            margin-top: 25px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(108, 93, 252, 0.04) 0%, rgba(167, 139, 250, 0.04) 100%);
            border-radius: 20px;
            border-left: 4px solid var(--accent-color);
            position: relative;
            z-index: 1;
        }
        
        .order-details.show {
            display: block;
            animation: slideDown 0.4s cubic-bezier(0.19, 1, 0.22, 1);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(108, 93, 252, 0.06);
            color: var(--primary-text);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 700;
            color: var(--primary-text);
        }
        
        .detail-value {
            text-align: right;
            color: var(--secondary-text);
            font-weight: 600;
        }
        
        .payment-badge {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
        }
        
        .payment-kpay {
            background: linear-gradient(135deg, #ffd97d 0%, #ffb84d 100%);
            color: #8b5a00;
        }
        
        .payment-wavepay {
            background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
            color: #065f46;
        }
        
        .payment-cash {
            background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
            color: #1e3a8a;
        }
        
        .no-orders { 
            text-align: center; 
            padding: 100px 40px;
            background: var(--card-bg);
            backdrop-filter: blur(30px);
            border-radius: 28px;
            border: 1px solid var(--card-border);
            box-shadow: 0 15px 50px rgba(44, 41, 109, 0.08);
        }
        
        .no-orders h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: var(--primary-text);
            margin-bottom: 15px;
            font-weight: 900;
        }
        
        .no-orders p {
            color: var(--secondary-text);
            font-size: 16px;
            margin-bottom: 30px;
        }

        .success-notification {
            background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
            color: #065f46;
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 700;
            position: relative;
            box-shadow: 0 8px 25px rgba(110, 231, 183, 0.3);
            border: 1px solid rgba(6, 95, 70, 0.1);
        }

        .close-notification {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(6, 95, 70, 0.1);
            border: none;
            color: #065f46;
            font-size: 20px;
            cursor: pointer;
            font-weight: bold;
            padding: 8px;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .close-notification:hover {
            background: rgba(6, 95, 70, 0.2);
            transform: translateY(-50%) rotate(90deg);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-glass">
                <h1>Order History</h1>
                <div class="user-pill">
                    <span style="opacity: 0.7;">Signed in as</span>
                    <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                </div>
                <div>
                    <nav class="nav-tabs-premium">
                        <a href="index.php" class="nav-link-premium">Collection</a>
                        <a href="cart.php" class="nav-link-premium">Cart</a>
                        <a href="orders.php" class="nav-link-premium active">My Orders</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <!-- Order Success Message -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'order_placed'): ?>
            <div style="background: linear-gradient(135deg, #9ae6b4 0%, #68d391 100%); color: #22543d; padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; font-weight: 600; position: relative;">
                🎉 Order placed successfully! Your delicious ice cream is being prepared.
                <button onclick="this.parentElement.style.display='none'" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #22543d; font-size: 20px; cursor: pointer; font-weight: bold; padding: 5px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                    ×
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Status Change Notifications -->
        <?php
        $hasNewStatusChanges = false;
        foreach ($orders as $order) {
            // Check if order was recently updated (within last 24 hours)
            $orderTime = strtotime($order['order_date']);
            $now = time();
            $timeDiff = $now - $orderTime;
            
            if ($order['status'] === 'completed' && $timeDiff < 86400) { // 24 hours
                $hasNewStatusChanges = true;
                break;
            }
        }
        
        if ($hasNewStatusChanges): ?>
            <div id="statusNotification" style="background: linear-gradient(135deg, #9ae6b4 0%, #68d391 100%); color: #22543d; padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; font-weight: 600; position: relative;">
                🎉 Great news! You have orders that are ready for pickup or have been delivered!
                <button onclick="closeNotification()" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #22543d; font-size: 20px; cursor: pointer; font-weight: bold; padding: 5px; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                    ×
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <h2>No orders yet</h2>
                <p>You haven't placed any orders yet. Start shopping!</p>
                <br>
                <a href="index.php" style="display: inline-block; padding: 14px 35px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 50px; font-weight: 600;">Start Shopping</a>
            </div>
        <?php endif; ?>
        
        <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-id">Order #<?= substr($order['id'], 0, 8) ?></div>
                    <div style="margin: 4px 0;">
                        <span style="font-weight: 700; color: #2d3748;"><?= htmlspecialchars($order['name']) ?></span>
                        <span style="color: #718096; font-size: 0.9em;">(<?= htmlspecialchars($order['email']) ?>)</span>
                    </div>
                    <div class="order-date" style="margin-bottom: 6px;"><?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></div>
                    <div style="font-size: 0.85rem; color: #4a5568; display: flex; gap: 10px; align-items: center;">
                        <span style="background: rgba(102, 126, 234, 0.1); padding: 2px 8px; border-radius: 4px; color: #5a67d8;">
                            <?= ($order['order_type'] ?? 'delivery') === 'dine-in' ? '🍽️ Dine-in' : '🚚 Delivery' ?>
                        </span>
                        <span style="color: #cbd5e0;">|</span>
                        <span><?= ucfirst($order['payment_method'] ?? 'cash') ?></span>
                    </div>
                </div>
                <div class="order-status-container">
                    <span class="order-status status-<?= $order['status'] ?>">
                        <?php
                        switch($order['status']) {
                            case 'pending':
                                echo '⏳ Processing';
                                break;
                            case 'completed':
                                echo '✅ Ready/Delivered';
                                break;
                            case 'cancelled':
                                echo '❌ Cancelled';
                                break;
                            default:
                                echo ucfirst($order['status']);
                        }
                        ?>
                    </span>
                    <div class="status-explanation">
                        <?php
                        switch($order['status']) {
                            case 'pending':
                                echo 'Your order is being prepared';
                                break;
                            case 'completed':
                                if (($order['order_type'] ?? 'delivery') === 'dine-in') {
                                    echo 'Your order is ready for pickup!';
                                } else {
                                    echo 'Your order has been delivered';
                                }
                                break;
                            case 'cancelled':
                                echo 'This order was cancelled';
                                break;
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="order-items">
                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name">
                            <?php
                            $fullName = $item['product_name'];
                            
                            // Parse format: "Flavor (Size) + Topping1, Topping2"
                            if (preg_match('/^(.+?)\s*\((.+?)\)(?:\s*\+\s*(.+))?$/', $fullName, $matches)) {
                                // Custom order
                                echo '🍦 <strong>' . htmlspecialchars($matches[1]) . '</strong>';
                                echo '<div class="item-description">';
                                echo '📏 ' . htmlspecialchars($matches[2]);
                                
                                // Show toppings if present
                                if (isset($matches[3]) && !empty($matches[3])) {
                                    echo '<br>🍫 ' . htmlspecialchars($matches[3]);
                                }
                                echo '</div>';
                            } else {
                                // Regular product
                                echo '🍨 <strong>' . htmlspecialchars($fullName) . '</strong>';
                            }
                            
                            // Show discount information if applicable
                            if (isset($item['discount_applied']) && $item['discount_applied'] > 0): ?>
                                <div style="font-size: 11px; color: #e53e3e; font-weight: 600; margin-top: 2px;">
                                    🏷️ <?= $item['discount_applied'] ?>% discount applied
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 13px; color: #718096; margin-top: 4px;">
                            <?php if (isset($item['original_price']) && $item['original_price'] > $item['price']): ?>
                                <span style="text-decoration: line-through; color: #999;">
                                    Qty: <?= $item['quantity'] ?> × <?= number_format($item['original_price'], 0) ?> MMK
                                </span>
                                <br>
                                <span style="color: #e53e3e; font-weight: 600;">
                                    Qty: <?= $item['quantity'] ?> × <?= number_format($item['price'], 0) ?> MMK
                                </span>
                            <?php else: ?>
                                Qty: <?= $item['quantity'] ?> × <?= number_format($item['price'], 0) ?> MMK
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="item-price">
                        <?php if (isset($item['original_price']) && $item['original_price'] > $item['price']): ?>
                            <div style="text-decoration: line-through; color: #999; font-size: 13px;">
                                <?= number_format($item['original_price'] * $item['quantity'], 0) ?> MMK
                            </div>
                            <div style="color: #e53e3e; font-weight: bold;">
                                <?= number_format($item['price'] * $item['quantity'], 0) ?> MMK
                            </div>
                        <?php else: ?>
                            <?= number_format($item['price'] * $item['quantity'], 0) ?> MMK
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
            <div style="text-align: right; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(102, 126, 234, 0.1);">
                <div style="font-size: 14px; color: #999; text-decoration: line-through;">
                    Original: <?= number_format($order['original_subtotal'] + ($order['delivery_fee'] ?? 0), 0) ?> MMK
                </div>
                <div style="font-size: 16px; color: #48bb78; font-weight: 600; margin-top: 2px;">
                    💰 You saved: <?= number_format($order['discount_amount'], 0) ?> MMK
                    <?php if ($order['discount_percentage'] > 0): ?>
                        (<?= number_format($order['discount_percentage'], 1) ?>% off)
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="order-total">Total: <span><?= number_format($order['total_price'], 0) ?> MMK</span></div>
            
            <div class="order-actions">
                <button class="details-btn" onclick="toggleDetails('order-<?= $order['id'] ?>')">
                    📋 View Details
                </button>
            </div>
            
            <div class="order-details" id="order-<?= $order['id'] ?>">
                <div class="detail-row">
                    <span class="detail-label">Order Type:</span>
                    <span class="detail-value">
                        <span class="payment-badge payment-<?= ($order['order_type'] ?? 'delivery') === 'dine-in' ? 'cash' : 'kpay' ?>">
                            <?= ($order['order_type'] ?? 'delivery') === 'dine-in' ? '🍽️ Dine-in' : '🚚 Delivery' ?>
                        </span>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value">
                        <span class="payment-badge payment-<?= $order['payment_method'] ?? 'cash' ?>">
                            <?php 
                            $paymentIcons = ['kpay' => '💳', 'wavepay' => '📱', 'cash' => '💵'];
                            $paymentNames = ['kpay' => 'KPay', 'wavepay' => 'WavePay', 'cash' => 'Cash on Delivery'];
                            $method = $order['payment_method'] ?? 'cash';
                            echo $paymentIcons[$method] . ' ' . $paymentNames[$method];
                            ?>
                        </span>
                    </span>
                </div>
                
                <?php if (!empty($order['delivery_township'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Delivery Township:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['delivery_township']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($order['delivery_address'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Delivery Address:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['delivery_address']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (isset($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                <div class="detail-row">
                    <span class="detail-label">Delivery Fee:</span>
                    <span class="detail-value"><?= number_format($order['delivery_fee'], 0) ?> MMK</span>
                </div>
                <?php endif; ?>
                
                <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                <div class="detail-row" style="border-top: 2px solid rgba(72, 187, 120, 0.2); padding-top: 15px; margin-top: 10px;">
                    <span class="detail-label" style="color: #48bb78;">💰 Discount Applied:</span>
                    <span class="detail-value" style="color: #48bb78; font-weight: bold;">
                        -<?= number_format($order['discount_amount'], 0) ?> MMK
                        <?php if ($order['discount_percentage'] > 0): ?>
                            (<?= number_format($order['discount_percentage'], 1) ?>% off)
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Original Subtotal:</span>
                    <span class="detail-value" style="text-decoration: line-through; color: #999;">
                        <?= number_format($order['original_subtotal'], 0) ?> MMK
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Final Subtotal:</span>
                    <span class="detail-value" style="color: #48bb78; font-weight: bold;">
                        <?= number_format($order['original_subtotal'] - $order['discount_amount'], 0) ?> MMK
                    </span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($order['phone'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Contact Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['phone']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($order['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Order Notes:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['notes']) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Customer Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['name']) ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Customer Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['email']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        function toggleDetails(orderId) {
            const detailsDiv = document.getElementById(orderId);
            const button = event.target;
            
            if (detailsDiv.classList.contains('show')) {
                detailsDiv.classList.remove('show');
                button.textContent = '📋 View Details';
            } else {
                detailsDiv.classList.add('show');
                button.textContent = '📋 Hide Details';
            }
        }
        
        function closeNotification() {
            const notification = document.getElementById('statusNotification');
            if (notification) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            }
        }
        
        // Add hover effect to close button
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.querySelector('#statusNotification button');
            if (closeBtn) {
                closeBtn.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(34, 84, 61, 0.1)';
                });
                closeBtn.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'transparent';
                });
            }
        });
    </script>
</body>
</html>
