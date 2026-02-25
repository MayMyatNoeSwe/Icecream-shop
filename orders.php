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
    <style>
        :root {
            --bg-color: #f1efe9;
            --primary-text: #2c296d;
            --accent-color: #6c5dfc;
            --secondary-text: #6b6b8d;
            --card-bg: rgba(255, 255, 255, 0.6);
            --card-border: rgba(255, 255, 255, 0.4);
        }

        [data-theme="dark"] {
            --bg-color: #1a1914;
            --primary-text: #f0f0f5;
            --accent-color: #a78bfa;
            --secondary-text: #c4c4d9;
            --card-bg: rgba(30, 30, 47, 0.7);
            --card-border: rgba(167, 139, 250, 0.2);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--primary-text);
            min-height: 100vh;
            margin: 0;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        nav {
            background: rgba(var(--bg-rgb), 0.8);
            backdrop-filter: blur(15px);
            padding: 20px 0;
            border-bottom: 1px solid var(--card-border);
            position: sticky; top: 0; z-index: 100;
        }

        .container { max-width: 900px; margin: 40px auto; padding: 0 24px; }

        .header-content { text-align: center; margin-bottom: 50px; }
        .header-content h1 { font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 900; margin-bottom: 10px; }

        .order-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 30px;
            border: 1px solid var(--card-border);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }
        .order-card:hover { transform: translateY(-5px); }

        .order-meta { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 1px solid var(--card-border); padding-bottom: 20px; }
        .order-info h3 { font-size: 0.85rem; font-weight: 800; color: var(--secondary-text); margin-bottom: 5px; opacity: 0.7; }
        .order-date { font-weight: 700; font-size: 1.1rem; }

        .status-badge {
            padding: 8px 18px;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending { background: rgba(250, 204, 21, 0.15); color: #ca8a04; }
        .status-completed { background: rgba(34, 197, 94, 0.15); color: #16a34a; }

        .item-list { margin-bottom: 25px; }
        .item-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed var(--card-border); }
        .item-row:last-child { border-bottom: none; }
        .item-name { font-weight: 700; font-size: 1rem; }
        .item-sub { font-size: 0.85rem; color: var(--secondary-text); margin-top: 4px; }

        .order-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--card-border); }
        .order-total { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 900; }

        .btn-view { border: none; background: var(--accent-color); color: white; padding: 10px 24px; border-radius: 100px; font-weight: 700; font-size: 0.9rem; transition: 0.3s; }
        .btn-view:hover { filter: brightness(1.1); transform: scale(1.05); }

        .empty-orders { text-align: center; padding: 80px 0; }
        .empty-icon { font-size: 4rem; opacity: 0.2; margin-bottom: 20px; }
        
        .back-home { position: fixed; top: 30px; left: 30px; text-decoration: none; color: var(--primary-text); font-weight: 800; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; z-index: 1000; }
    </style>
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
