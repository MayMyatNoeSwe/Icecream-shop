<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

$orders = [];
try {
    $db = Database::getInstance()->getConnection();
    $userEmail = $_SESSION['user_email'];
    $stmt = $db->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email = ? ORDER BY o.order_date DESC");
    $stmt->execute([$userEmail]);
    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $stmt = $db->prepare("SELECT oi.*, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | Scoops Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/orders.css">
</head>
<body>
    <a href="index.php" class="back-home"><i class="bi bi-arrow-left"></i> Gallery</a>

    <div class="container">
        <div class="header-content">
            <h1>Order History</h1>
            <p style="color:var(--secondary-text); font-weight:600;">Relive your sweetest moments</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                <h2 style="font-family:'Playfair Display';">No orders yet</h2>
                <a href="index.php" class="btn-view d-inline-block mt-3 text-decoration-none">Start Exploring</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-meta">
                        <div class="order-info">
                            <h3>ORDER #<?= substr($order['id'], 0, 8) ?></h3>
                            <div class="order-date"><?= date('M d, Y • g:i A', strtotime($order['order_date'])) ?></div>
                        </div>
                        <div class="status-badge status-<?= $order['status'] ?>">
                            <?= $order['status'] === 'completed' ? 'Ready' : 'In Progress' ?>
                        </div>
                    </div>

                    <div class="item-list">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="item-row">
                                <div style="display: flex; gap: 15px; align-items: center;">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/placeholder.png') ?>" 
                                         style="width: 50px; height: 50px; border-radius: 12px; object-fit: cover; background: rgba(108, 93, 252, 0.05); border: 1px solid var(--card-border);">
                                    <div>
                                        <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <div class="item-sub">Qty: <?= $item['quantity'] ?> × <?= number_format($item['price'], 0) ?> MMK</div>
                                    </div>
                                </div>
                                <div style="font-weight:700; color: var(--accent-color);"><?= number_format($item['price'] * $item['quantity'], 0) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-footer">
                        <div class="order-total"><?= number_format($order['total_price'], 0) ?> <span style="font-size: 0.9rem; font-family: sans-serif;">MMK</span></div>
                        <button class="btn-view" onclick="alert('Order tracking details are being updated by the kitchen.')">Track Order</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</body>
</html>
