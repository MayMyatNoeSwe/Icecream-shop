<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

$orders = [];
$successMessage = '';
$errorMessage = '';
if (isset($_GET['reorder'])) {
    if ($_GET['reorder'] === 'success') {
        $successMessage = 'Items added to cart successfully!';
    } elseif ($_GET['reorder'] === 'failed_stock') {
        $errorMessage = 'Sorry, we couldn\'t reorder these items because they are currently out of stock.';
    } elseif ($_GET['reorder'] === 'error') {
        $errorMessage = 'An error occurred while processing your reorder. Please try again.';
    }
}

// Separate into active and history
$activeOrders = [];
$historyOrders = [];

try {
    $db = Database::getInstance()->getConnection();
    date_default_timezone_set('Asia/Yangon');
    $userEmail = $_SESSION['user_email'];
    $stmt = $db->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email = ? ORDER BY o.order_date DESC");
    $stmt->execute([$userEmail]);
    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $stmt = $db->prepare("SELECT oi.*, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();

        // Automatic Status Update Logic
        $orderTime = strtotime($order['order_date']);
        $now = time();
        $minutesElapsed = ($now - $orderTime) / 60;

        if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed') {
            if ($minutesElapsed >= 15) {
                $order['status'] = 'completed';
            } elseif ($minutesElapsed >= 10) {
                $order['status'] = 'ready';
            } elseif ($minutesElapsed >= 5) {
                $order['status'] = 'preparing';
            } else {
                $order['status'] = 'pending';
            }
        }
        $order['minutes_elapsed'] = max(0, $minutesElapsed);

        // Separate orders
        if (in_array($order['status'], ['pending', 'preparing', 'ready'])) {
            $activeOrders[] = $order;
        } else {
            $historyOrders[] = $order;
        }
    }
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Scoops Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Slabo+27px&family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/orders.css">
    <style>
        .orders-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: calc(var(--nav-height, 75px) + 20px) 24px 60px;
        }
        .playfair { font-family: 'Playfair Display', serif; }
        .jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Section Titles */
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            margin-top: 40px;
        }
        .section-header:first-child { margin-top: 10px; }
        .section-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .section-icon.active-icon {
            background: rgba(108, 93, 252, 0.1);
            color: var(--accent-color, #6c5dfc);
        }
        .section-icon.history-icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary-text, #2c296d);
        }
        .section-count {
            font-size: 0.7rem;
            font-weight: 800;
            background: rgba(108, 93, 252, 0.08);
            color: var(--accent-color, #6c5dfc);
            padding: 3px 10px;
            border-radius: 50px;
        }

        /* Active Order Cards */
        .total-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--secondary-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .actions-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn-reorder {
            background: var(--accent-color, #6c5dfc);
            color: white;
        }
        .btn-reorder:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }
        .detail-item h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--secondary-text, #6b6b8d);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .detail-item p {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-text, #2c296d);
            margin: 0;
        }

        /* No Active Orders */
        .no-active-orders {
            text-align: center;
            padding: 40px 20px;
            background: var(--card-bg, rgba(255,255,255,0.4));
            border-radius: 20px;
            border: 1px dashed var(--card-border, rgba(255,255,255,0.3));
        }
        .no-active-orders i {
            font-size: 2rem;
            color: var(--secondary-text, #6b6b8d);
            opacity: 0.4;
            margin-bottom: 10px;
        }
        .no-active-orders p {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--secondary-text, #6b6b8d);
            margin: 0;
        }

        /* ═══ History Table ═══ */
        .history-table-wrapper {
            background: var(--card-bg, rgba(255,255,255,0.4));
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid var(--card-border, rgba(255,255,255,0.3));
            overflow: hidden;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .history-table thead th {
            padding: 14px 18px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--secondary-text, #6b6b8d);
            background: rgba(108, 93, 252, 0.03);
            border-bottom: 1px solid var(--card-border, rgba(255,255,255,0.3));
            text-align: left;
            white-space: nowrap;
        }
        .history-table tbody tr {
            transition: background 0.2s ease;
        }
        .history-table tbody tr:hover {
            background: rgba(108, 93, 252, 0.03);
        }
        .history-table tbody td {
            padding: 16px 18px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--primary-text, #2c296d);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            vertical-align: middle;
        }
        .history-table tbody tr:last-child td {
            border-bottom: none;
        }
        .history-order-id {
            font-weight: 800;
            font-size: 0.82rem;
            color: var(--accent-color, #6c5dfc);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .history-date {
            font-size: 0.82rem;
            color: var(--secondary-text, #6b6b8d);
        }
        .history-items-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-width: 250px;
        }
        .history-item-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            background: rgba(108, 93, 252, 0.06);
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--primary-text, #2c296d);
            white-space: nowrap;
        }
        .history-item-qty {
            color: var(--accent-color, #6c5dfc);
            font-weight: 800;
        }
        .history-total {
            font-weight: 800;
            font-size: 0.95rem;
            white-space: nowrap;
        }
        .history-total-currency {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--secondary-text, #6b6b8d);
            margin-left: 2px;
        }
        .history-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .history-status.completed {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }
        .history-status.cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        .btn-reorder-sm {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 50px;
            border: none;
            background: var(--accent-color, #6c5dfc);
            color: white;
            font-size: 0.75rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
            white-space: nowrap;
        }
        .btn-reorder-sm:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
            box-shadow: 0 4px 12px rgba(108, 93, 252, 0.3);
        }
        .btn-view-sm {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 50px;
            border: 1px solid rgba(108, 93, 252, 0.15);
            background: rgba(108, 93, 252, 0.06);
            color: var(--accent-color, #6c5dfc);
            font-size: 0.75rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
            white-space: nowrap;
            text-decoration: none;
        }
        .btn-view-sm:hover {
            background: var(--accent-color, #6c5dfc);
            color: white;
        }
        .history-actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .no-history {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary-text, #6b6b8d);
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Divider */
        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, var(--card-border, rgba(0,0,0,0.06)), transparent);
            margin: 35px 0 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .history-table thead { display: none; }
            .history-table tbody tr {
                display: block;
                padding: 16px;
                margin-bottom: 10px;
                background: var(--card-bg, rgba(255,255,255,0.4));
                border-radius: 14px;
                border: 1px solid var(--card-border, rgba(255,255,255,0.3));
            }
            .history-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: none;
                font-size: 0.85rem;
            }
            .history-table tbody td::before {
                content: attr(data-label);
                font-size: 0.7rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: var(--secondary-text, #6b6b8d);
                flex-shrink: 0;
                margin-right: 12px;
            }
            .history-items-list { justify-content: flex-end; }
        }

        /* ═══ Receipt-style Modal ═══ */
        .history-detail-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(20, 18, 50, 0.55);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .history-detail-overlay.show {
            display: flex;
            animation: overlayFadeIn 0.25s ease;
        }
        @keyframes overlayFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .receipt-card {
            background: #ffffff;
            border-radius: 28px;
            max-width: 440px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 40px 80px rgba(44, 41, 109, 0.25), 0 0 0 1px rgba(255,255,255,0.1);
            animation: receiptSlide 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
            scrollbar-width: none;
        }
        .receipt-card::-webkit-scrollbar { display: none; }
        @keyframes receiptSlide {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .receipt-header {
            background: linear-gradient(135deg, #2c296d 0%, #6c5dfc 100%);
            padding: 24px 28px 20px;
            border-radius: 28px 28px 0 0;
            position: relative;
            overflow: hidden;
        }
        .receipt-header::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0; right: 0;
            height: 16px;
            background: radial-gradient(circle at 10px 0, transparent 10px, #fff 10px);
            background-size: 20px 16px;
        }
        .receipt-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .receipt-order-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.5);
            margin-bottom: 4px;
        }
        .receipt-order-id {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.02em;
        }
        .receipt-date {
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            margin-top: 3px;
        }
        .receipt-close {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            width: 34px; height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .receipt-close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        .receipt-body {
            padding: 20px 28px 6px;
        }
        .receipt-section-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--secondary-text, #6b6b8d);
            margin-bottom: 12px;
            opacity: 0.7;
        }
        .receipt-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(44, 41, 109, 0.05);
        }
        .receipt-item:last-child { border-bottom: none; }
        .receipt-item-img {
            width: 50px; height: 50px;
            border-radius: 14px;
            object-fit: cover;
            border: 2px solid rgba(108, 93, 252, 0.08);
            flex-shrink: 0;
        }
        .receipt-item-info { flex: 1; min-width: 0; }
        .receipt-item-name {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--primary-text, #2c296d);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .receipt-item-meta {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--secondary-text, #6b6b8d);
            margin-top: 2px;
        }
        .receipt-item-meta span {
            color: var(--accent-color, #6c5dfc);
            font-weight: 700;
        }
        .receipt-item-total {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--primary-text, #2c296d);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .receipt-dashed-sep {
            border: none;
            border-top: 2px dashed rgba(44, 41, 109, 0.08);
            margin: 4px 0 16px;
        }
        .receipt-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            background: rgba(108, 93, 252, 0.03);
            border-radius: 16px;
            padding: 18px;
            border: 1px solid rgba(108, 93, 252, 0.06);
        }
        .receipt-info-item {}
        .receipt-info-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--secondary-text, #6b6b8d);
            margin-bottom: 4px;
            opacity: 0.8;
        }
        .receipt-info-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--primary-text, #2c296d);
        }
        .receipt-footer {
            padding: 0 28px 24px;
        }
        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0 0;
        }
        .receipt-total-left {}
        .receipt-total-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--secondary-text, #6b6b8d);
            margin-bottom: 2px;
        }
        .receipt-total-amount {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--primary-text, #2c296d);
            letter-spacing: -0.02em;
        }
        .receipt-total-currency {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--secondary-text, #6b6b8d);
            margin-left: 3px;
        }
        .receipt-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .receipt-status-pill.s-completed {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.15);
        }
        .receipt-status-pill.s-cancelled {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.15);
        }
        .receipt-reorder-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            margin-top: 16px;
            border-radius: 16px;
            border: none;
            background: linear-gradient(135deg, #2c296d, #6c5dfc);
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.02em;
        }
        .receipt-reorder-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 93, 252, 0.35);
            filter: brightness(1.05);
        }
    </style>

</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="orders-wrapper">
        <div class="header-content pt-4">
            <h1 class="playfair">My Orders</h1>
            <p class="jakarta" style="color: var(--secondary-text); margin-top: 4px;">Track your scoops from kitchen to table</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                <h2 class="playfair mb-3">No orders placed yet</h2>
                <p class="text-muted mb-4">Your journey of flavors is just one scoop away.</p>
                <a href="index.php" class="btn-premium btn-reorder">Start Exploring</a>
            </div>
        <?php else: ?>

            <!-- ═══════════════════════════════════ -->
            <!-- SECTION 1: CURRENT / ACTIVE ORDERS  -->
            <!-- ═══════════════════════════════════ -->
            <div class="section-header">
                <div class="section-icon active-icon"><i class="bi bi-radar"></i></div>
                <div>
                    <div class="section-title">Current Orders <span class="section-count"><?= count($activeOrders) ?></span></div>
                </div>
            </div>

            <?php if (empty($activeOrders)): ?>
                <div class="no-active-orders">
                    <i class="bi bi-check-circle d-block"></i>
                    <p>No active orders right now. All caught up! 🎉</p>
                </div>
            <?php else: ?>
                <?php foreach ($activeOrders as $order): ?>
                    <div class="order-card">
                        <div class="order-meta">
                            <div class="order-info" onclick="toggleCard('<?= $order['id'] ?>')" style="cursor: pointer;">
                                <h3 style="display: flex; align-items: center; gap: 8px;">
                                    ORDER #<?= substr($order['id'], 0, 8) ?>
                                    <i class="bi bi-chevron-down chevron-icon" style="font-size: 0.8rem; opacity: 0.5;"></i>
                                </h3>
                                <div class="order-date"><?= date('F j, Y • g:i A', strtotime($order['order_date'])) ?></div>
                            </div>
                            <div class="status-badge status-<?= $order['status'] ?>">
                                <?php if($order['status'] === 'pending'): ?>
                                    <i class="bi bi-receipt"></i> Processing
                                <?php elseif($order['status'] === 'preparing'): ?>
                                    <i class="bi bi-fire"></i> Preparing
                                <?php elseif($order['status'] === 'ready'): ?>
                                    <i class="bi bi-bell-fill"></i> Ready
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="collapsible-<?= $order['id'] ?>" class="card-collapsible">
                            <div class="item-list">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="item-row">
                                        <div class="d-flex align-items-center gap-3">
                                            <?php 
                                                $img = htmlspecialchars($item['image_url'] ?? 'images/placeholder.png');
                                                if (empty($img)) $img = 'images/placeholder.png';
                                            ?>
                                            <img src="<?= $img ?>" class="item-img" alt="Product">
                                            <div>
                                                <div class="item-name"><?= htmlspecialchars(explode(' (', $item['product_name'])[0]) ?></div>
                                                <div class="item-sub">Quantity: <?= $item['quantity'] ?> • <?= number_format($item['price'], 0) ?> MMK</div>
                                            </div>
                                        </div>
                                        <div class="item-price"><?= number_format($item['price'] * $item['quantity'], 0) ?> MMK</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="stepper-container">
                                <div class="d-flex justify-content-between mb-3 px-1 align-items-center">
                                    <span class="fw-800 text-uppercase jakarta" style="font-size: 0.7rem; color: var(--accent-color); letter-spacing: 1.5px;">
                                        <i class="bi bi-radar me-1"></i> Live Tracker
                                    </span>
                                    <span class="fw-800 jakarta" style="font-size: 0.75rem; color: var(--secondary-text);">
                                        Current Stage: <?= $order['status'] === 'preparing' ? 'Kitchen' : ucfirst($order['status']) ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3 px-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span style="font-size: 0.7rem; font-weight: 700; color: var(--secondary-text);">AVERAGE WAITING TIME</span>
                                        <span style="font-size: 0.7rem; font-weight: 800; color: var(--accent-color);">15 MINS</span>
                                    </div>
                                    <div class="progress">
                                        <?php 
                                            $progress = min(($order['minutes_elapsed'] / 15) * 100, 100);
                                        ?>
                                        <div class="progress-bar" role="progressbar" style="--final-width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="position-relative d-flex justify-content-between align-items-center" style="padding: 0 15px;">
                                    <div style="position: absolute; top: 50%; left: 0; right: 0; height: 3px; background: rgba(108, 93, 252, 0.1); transform: translateY(-50%); z-index: 1;"></div>
                                    <?php 
                                        $statuses = ['pending', 'preparing', 'ready', 'completed'];
                                        $currentIdx = array_search($order['status'], $statuses);
                                        if ($currentIdx === false) $currentIdx = -1;
                                        
                                        $icons = [
                                            'pending' => 'bi-receipt',
                                            'preparing' => 'bi-fire',
                                            'ready' => 'bi-bell',
                                            'completed' => 'bi-check2-circle'
                                        ];
                                        
                                        foreach ($statuses as $idx => $s):
                                            $active = $idx <= $currentIdx;
                                            $glow = $idx === $currentIdx;
                                    ?>
                                    <div class="step-badge" style="z-index: 2; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: <?= $active ? 'var(--accent-color)' : 'var(--white)' ?>; border: 2.5px solid <?= $active ? 'var(--accent-color)' : 'var(--card-border)' ?>; color: <?= $active ? 'white' : 'var(--secondary-text)' ?>; transition: all 0.5s ease; box-shadow: <?= $glow ? '0 0 20px rgba(108, 93, 252, 0.6)' : 'none' ?>;">
                                        <i class="bi <?= $icons[$s] ?>" style="font-size: 1.1rem;"></i>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex justify-content-between mt-3 px-1" style="font-size: 0.6rem; font-weight: 800; text-transform: uppercase; color: var(--secondary-text); letter-spacing: 0.5px; opacity: 0.7;">
                                    <span>Ordered</span>
                                    <span>Kitchen</span>
                                    <span>Ready</span>
                                    <span>Delivered</span>
                                </div>
                            </div>

                            <div id="details-<?= $order['id'] ?>" class="order-details-pane">
                                <div class="details-grid">
                                    <div class="detail-item">
                                        <h4>Payment Method</h4>
                                        <p><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></p>
                                    </div>
                                    <div class="detail-item">
                                        <h4>Service Location</h4>
                                        <p><?= !empty($order['table_number']) ? $order['table_number'] : 'Dine-In / Guest' ?></p>
                                    </div>
                                    <div class="detail-item">
                                        <h4>Contact Trace</h4>
                                        <p><?= htmlspecialchars($order['phone']) ?></p>
                                    </div>
                                    <div class="detail-item">
                                        <h4>Notes for Artisan</h4>
                                        <p><?= !empty($order['notes']) ? htmlspecialchars($order['notes']) : 'No specific requests.' ?></p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-top border-secondary-subtle">
                                     <div class="d-flex justify-content-between mb-1" style="font-size: 0.9rem; font-weight: 700;">
                                        <span>Subtotal</span>
                                        <?php 
                                            $deliveryFee = (float)($order['delivery_fee'] ?? 0);
                                            $discount = (float)($order['discount_amount'] ?? 0);
                                            $subtotal = $order['original_subtotal'] ?? ($order['total_price'] - $deliveryFee + $discount);
                                        ?>
                                        <span><?= number_format($subtotal, 0) ?> MMK</span>
                                    </div>
                                    <?php if ($discount > 0): ?>
                                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.9rem; font-weight: 700; color: var(--accent-color);">
                                        <span>Promo Discount (<?= htmlspecialchars($order['coupon_code'] ?? 'SAVINGS') ?>)</span>
                                        <span>-<?= number_format($discount, 0) ?> MMK</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div>
                                <div class="total-label">Total</div>
                                <div class="order-total"><?= number_format($order['total_price'], 0) ?> <span style="font-size: 1rem; font-family: sans-serif;">MMK</span></div>
                            </div>
                            <div class="actions-group">
                                <button class="btn-premium btn-details" onclick="toggleDetails('<?= $order['id'] ?>', this)">View Details</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- ═══════════════════════════════════ -->
            <!-- SECTION 2: ORDER HISTORY TABLE      -->
            <!-- ═══════════════════════════════════ -->
            <div class="section-divider"></div>

            <div class="section-header">
                <div class="section-icon history-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="section-title">Order History <span class="section-count" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><?= count($historyOrders) ?></span></div>
                </div>
            </div>

            <?php if (empty($historyOrders)): ?>
                <div class="no-active-orders">
                    <i class="bi bi-bag-check d-block"></i>
                    <p>No past orders yet. Place your first order!</p>
                </div>
            <?php else: ?>
                <div class="history-table-wrapper">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyOrders as $hOrder): ?>
                            <tr>
                                <td data-label="Order ID">
                                    <span class="history-order-id">#<?= substr($hOrder['id'], 0, 8) ?></span>
                                </td>
                                <td data-label="Date">
                                    <span class="history-date"><?= date('M j, Y', strtotime($hOrder['order_date'])) ?></span>
                                </td>
                                <td data-label="Items">
                                    <div class="history-items-list">
                                        <?php 
                                        $displayItems = array_slice($hOrder['items'], 0, 3);
                                        foreach ($displayItems as $hItem): ?>
                                            <span class="history-item-chip">
                                                <span class="history-item-qty"><?= $hItem['quantity'] ?>×</span>
                                                <?= htmlspecialchars(mb_strimwidth(explode(' (', $hItem['product_name'])[0], 0, 16, '…')) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (count($hOrder['items']) > 3): ?>
                                            <span class="history-item-chip" style="opacity: 0.6;">+<?= count($hOrder['items']) - 3 ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Total">
                                    <span class="history-total"><?= number_format($hOrder['total_price'], 0) ?><span class="history-total-currency">MMK</span></span>
                                </td>
                                <td data-label="Status">
                                    <span class="history-status <?= $hOrder['status'] ?>">
                                        <?php if ($hOrder['status'] === 'completed'): ?>
                                            <i class="bi bi-check2-circle"></i> Served
                                        <?php else: ?>
                                            <i class="bi bi-x-circle"></i> Cancelled
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <div class="history-actions">
                                        <button class="btn-view-sm" onclick="showHistoryDetail('<?= $hOrder['id'] ?>')">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <?php if ($hOrder['status'] === 'completed'): ?>
                                            <form action="reorder.php" method="POST" style="margin: 0;">
                                                <input type="hidden" name="order_id" value="<?= $hOrder['id'] ?>">
                                                <button type="submit" class="btn-reorder-sm">
                                                    <i class="bi bi-arrow-repeat"></i> Reorder
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- History Detail Overlays (Receipt Style) -->
    <?php foreach ($historyOrders as $hOrder): ?>
    <div class="history-detail-overlay" id="history-overlay-<?= $hOrder['id'] ?>">
        <div class="receipt-card">
            <!-- Gradient Header -->
            <div class="receipt-header">
                <div class="receipt-header-top">
                    <div>
                        <div class="receipt-order-label">Order Receipt</div>
                        <div class="receipt-order-id">#<?= substr($hOrder['id'], 0, 8) ?></div>
                        <div class="receipt-date"><?= date('M j, Y • g:i A', strtotime($hOrder['order_date'])) ?></div>
                    </div>
                    <button class="receipt-close" onclick="closeHistoryDetail('<?= $hOrder['id'] ?>')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Items -->
            <div class="receipt-body">
                <div class="receipt-section-label">Items Ordered</div>
                <?php foreach ($hOrder['items'] as $hItem): ?>
                <div class="receipt-item">
                    <?php 
                        $hImg = htmlspecialchars($hItem['image_url'] ?? 'images/placeholder.png');
                        if (empty($hImg)) $hImg = 'images/placeholder.png';
                    ?>
                    <img src="<?= $hImg ?>" class="receipt-item-img" alt="Product">
                    <div class="receipt-item-info">
                        <div class="receipt-item-name"><?= htmlspecialchars(explode(' (', $hItem['product_name'])[0]) ?></div>
                        <div class="receipt-item-meta"><span><?= $hItem['quantity'] ?>×</span> <?= number_format($hItem['price'], 0) ?> MMK</div>
                    </div>
                    <div class="receipt-item-total"><?= number_format($hItem['price'] * $hItem['quantity'], 0) ?> MMK</div>
                </div>
                <?php endforeach; ?>

                <hr class="receipt-dashed-sep">

                <!-- Info Grid -->
                <div class="receipt-section-label">Details</div>
                <div class="receipt-info-grid">
                    <div class="receipt-info-item">
                        <div class="receipt-info-label"><i class="bi bi-wallet2 me-1"></i> Payment</div>
                        <div class="receipt-info-value"><?= ucfirst(str_replace('_', ' ', $hOrder['payment_method'])) ?></div>
                    </div>
                    <div class="receipt-info-item">
                        <div class="receipt-info-label"><i class="bi bi-geo-alt me-1"></i> Location</div>
                        <div class="receipt-info-value"><?= !empty($hOrder['table_number']) ? $hOrder['table_number'] : 'Dine-In' ?></div>
                    </div>
                    <?php if (!empty($hOrder['phone'])): ?>
                    <div class="receipt-info-item">
                        <div class="receipt-info-label"><i class="bi bi-telephone me-1"></i> Contact</div>
                        <div class="receipt-info-value"><?= htmlspecialchars($hOrder['phone']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($hOrder['notes'])): ?>
                    <div class="receipt-info-item">
                        <div class="receipt-info-label"><i class="bi bi-chat-text me-1"></i> Notes</div>
                        <div class="receipt-info-value"><?= htmlspecialchars($hOrder['notes']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <div class="receipt-footer">
                <div class="receipt-total-row">
                    <div class="receipt-total-left">
                        <div class="receipt-total-label">Total Paid</div>
                        <div class="receipt-total-amount">
                            <?= number_format($hOrder['total_price'], 0) ?><span class="receipt-total-currency">MMK</span>
                        </div>
                    </div>
                    <span class="receipt-status-pill s-<?= $hOrder['status'] ?>">
                        <?php if ($hOrder['status'] === 'completed'): ?>
                            <i class="bi bi-check2-circle"></i> Served
                        <?php else: ?>
                            <i class="bi bi-x-circle"></i> Cancelled
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($hOrder['status'] === 'completed'): ?>
                <form action="reorder.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="order_id" value="<?= $hOrder['id'] ?>">
                    <button type="submit" class="receipt-reorder-btn">
                        <i class="bi bi-arrow-repeat"></i> Order Again
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Active order card toggle
        function toggleCard(orderId) {
            const content = document.getElementById('collapsible-' + orderId);
            const card = content.closest('.order-card');
            const isVisible = content.style.display === 'block';
            
            if (isVisible) {
                content.style.display = 'none';
                card.classList.remove('expanded');
            } else {
                content.style.display = 'block';
                card.classList.add('expanded');
            }
        }

        function toggleDetails(orderId, btn) {
            const detailsBox = document.getElementById('details-' + orderId);
            const content = document.getElementById('collapsible-' + orderId);
            const card = content.closest('.order-card');
            const isVisible = detailsBox.style.display === 'block';
            
            if (!isVisible) {
                content.style.display = 'block';
                card.classList.add('expanded');
            }
            
            if (!btn) {
                const allBtns = document.querySelectorAll('.btn-details');
                for (let b of allBtns) {
                    if (b.getAttribute('onclick') && b.getAttribute('onclick').includes(orderId)) {
                        btn = b;
                        break;
                    }
                }
            }
            
            if (isVisible) {
                detailsBox.style.display = 'none';
                if (btn) btn.innerHTML = 'View Details';
            } else {
                detailsBox.style.display = 'block';
                if (btn) btn.innerHTML = 'Hide Details';
            }
        }

        // History detail overlay
        function showHistoryDetail(orderId) {
            document.getElementById('history-overlay-' + orderId).classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeHistoryDetail(orderId) {
            document.getElementById('history-overlay-' + orderId).classList.remove('show');
            document.body.style.overflow = '';
        }

        // Close overlay on background click
        document.querySelectorAll('.history-detail-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });

        <?php if ($successMessage): ?>
            Swal.fire({
                icon: 'success',
                title: 'Great Choice!',
                text: '<?= $successMessage ?>',
                confirmButtonColor: '#6c5dfc'
            });
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            Swal.fire({
                icon: 'error',
                title: 'Wait a moment...',
                text: '<?= $errorMessage ?>',
                confirmButtonColor: '#ef4444'
            });
        <?php endif; ?>

    </script>
</body>
</html>
