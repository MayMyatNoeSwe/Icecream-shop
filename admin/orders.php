<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get orders
    $stmt = $db->query("SELECT o.*, u.name as user_name, u.email as user_email 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       ORDER BY o.order_date DESC");
    $orders = $stmt->fetchAll();
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
    
    // Get statistics
    $stats = [
        'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'total_orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
        'total_customers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    ];
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c5dfc;
            --primary-light: #a78bfa;
            --secondary: #1e1e2f;
            --bg-color: #f1efe9;
            --surface: #ffffff;
            --text-main: #2c296d;
            --text-muted: #6b6b8d;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --card-shadow: 0 10px 30px rgba(44, 41, 109, 0.05);
            --transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        .dashboard-container {
            width: 100%;
            display: flex;
        }

        h1, h2, h3 { font-family: 'Playfair Display', serif; }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background: var(--surface);
            color: var(--text-main);
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(44, 41, 109, 0.08);
            z-index: 100;
            transition: var(--transition);
        }

        .sidebar-header { padding: 0 1.5rem 1.5rem; text-align: center; }
        .sidebar-logo { text-decoration: none; color: var(--primary); font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .sidebar-logo i { background: linear-gradient(135deg, var(--primary), var(--primary-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 2rem; }

        .sidebar-nav { padding: 0; }
        .nav-section { margin-bottom: 2rem; }
        .nav-section-title { padding: 0 2.5rem; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem; letter-spacing: 1.5px; opacity: 0.7; }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border-left: 4px solid transparent;
            font-size: 0.95rem;
        }
        
        .nav-link:hover { background: rgba(108, 93, 252, 0.04); color: var(--primary); padding-left: 2.25rem; }
        .nav-link.active { background: rgba(108, 93, 252, 0.08); color: var(--primary); border-left-color: var(--primary); }
        .nav-link i { width: 22px; text-align: center; font-size: 1.2rem; }
        
        .nav-link .badge {
            margin-left: auto;
            background: var(--danger);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 800;
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.2);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 1.25rem 1.5rem;
            width: calc(100% - 250px);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .header-title h1 { font-size: 1.8rem; color: var(--text-main); margin-bottom: 0.25rem; }
        .header-title p { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }

        /* Order Cards Styling */
        .order-card {
            background: var(--surface);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition);
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(108, 93, 252, 0.08);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(44, 41, 109, 0.05);
        }

        .order-id-badge { font-size: 0.75rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; background: rgba(108, 93, 252, 0.05); padding: 4px 10px; border-radius: 8px; margin-bottom: 8px; display: inline-block; }
        .customer-name { font-size: 1.2rem; font-weight: 900; color: var(--text-main); margin: 2px 0; }
        .customer-email { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; display: block; }

        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
        }
        .order-meta span i { margin-right: 6px; color: var(--primary); }

        .status-actions-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }

        .status-select {
            padding: 10px 18px;
            border-radius: 12px;
            border: 2px solid rgba(108, 93, 252, 0.1);
            font-family: inherit;
            font-weight: 800;
            font-size: 0.85rem;
            color: var(--text-main);
            background: #f8fbff;
            cursor: pointer;
            transition: var(--transition);
            min-width: 180px;
        }

        .status-select:hover { border-color: var(--primary); background: white; transform: scale(1.02); }

        .order-items-panel { 
            background: rgba(44, 41, 109, 0.02);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin: 1.5rem 0;
        }
        
        .order-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed rgba(44, 41, 109, 0.08);
        }
        .order-item-row:last-child { border-bottom: none; }

        .item-main { display: flex; align-items: center; gap: 15px; }
        .item-details { display: flex; flex-direction: column; }
        .item-name { font-weight: 800; color: var(--text-main); font-size: 0.95rem; }
        .item-options { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }
        .item-price-unit { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); opacity: 0.7; }

        .item-qty-badge { font-size: 0.8rem; color: var(--primary); font-weight: 800; background: rgba(108, 93, 252, 0.1); padding: 4px 10px; border-radius: 8px; border: 1px solid rgba(108, 93, 252, 0.1); }

        .item-total-price { font-weight: 900; color: var(--text-main); font-size: 1rem; }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(44, 41, 109, 0.05);
        }

        .footer-total-box { text-align: right; }
        .total-label { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 4px; }
        .total-value-main { font-size: 1.8rem; font-weight: 900; color: var(--primary); }
        .total-currency { font-size: 0.8rem; font-weight: 800; color: var(--text-muted); margin-left: 2px; }

        .order-notes-premium {
            background: #fff8eb;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            font-size: 0.85rem;
            line-height: 1.6;
            color: #856404;
            border-left: 5px solid var(--warning);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.05);
        }
        .order-notes-premium strong { color: #533f03; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px; }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--surface);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        .empty-state i { font-size: 5rem; color: rgba(108, 93, 252, 0.1); margin-bottom: 1.5rem; }
        .empty-state h2 { font-size: 1.8rem; color: var(--text-main); margin-bottom: 0.5rem; }
        .empty-state p { color: var(--text-muted); font-weight: 500; }

        .success-banner { background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 12px 20px; border-radius: 14px; margin-bottom: 1.5rem; font-weight: 700; font-size: 0.9rem; border: 1px solid rgba(16, 185, 129, 0.2); display: flex; align-items: center; gap: 10px; }

        /* Status Colors */
        .status-pending { background: rgba(245, 158, 11, 0.1) !important; color: var(--warning) !important; border-color: rgba(245, 158, 11, 0.2) !important; }
        .status-completed { background: rgba(16, 185, 129, 0.1) !important; color: var(--success) !important; border-color: rgba(16, 185, 129, 0.2) !important; }
        .status-cancelled { background: rgba(239, 68, 68, 0.1) !important; color: var(--danger) !important; border-color: rgba(239, 68, 68, 0.2) !important; }

        /* Media Queries */
        @media (max-width: 992px) {
            .order-header { flex-direction: column; gap: 15px; }
            .status-actions-container { align-items: flex-start; }
            .status-select { min-width: 100%; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; padding: 1.5rem; }
        }
    </style>
</head>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-ice-cream"></i>
                    <span>Scoops Admin</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="orders.php" class="nav-link active">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Orders</span>
                        <?php if ($stats['pending_orders'] > 0): ?>
                            <span class="badge"><?= $stats['pending_orders'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="coupons.php" class="nav-link">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Coupons</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Products</div>
                    <a href="product.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        <span>Manage Products</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Other</div>
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="header-title">
                    <h1>Order Fulfillment</h1>
                    <p>Manage and track premium guest orders</p>
                </div>
            </div>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'status_updated'): ?>
                <div class="success-banner">
                    <i class="fas fa-check-circle"></i>
                    <span>Excellence! The order status has been successfully refined.</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="success-banner" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); border-color: rgba(239, 68, 68, 0.2);">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>
                        <?php 
                            switch($_GET['error']) {
                                case 'invalid_status': echo 'Invalid status selected'; break;
                                case 'update_failed': echo 'Failed to update order status'; break;
                                default: echo 'An error occurred during fulfillment'; break;
                            }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h2>No orders on progress</h2>
                    <p>Your premium catalog is waiting for new guest requests.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id-badge">Order #<?= substr($order['id'], 0, 8) ?></span>
                            <h2 class="customer-name"><?= htmlspecialchars($order['user_name'] ?? 'Guest Connoisseur') ?></h2>
                            <span class="customer-email"><?= htmlspecialchars($order['user_email'] ?? 'Premium Guest') ?></span>
                            
                            <div class="order-meta">
                                <span><i class="far fa-calendar-alt"></i> <?= date('F j, Y', strtotime($order['order_date'])) ?></span>
                                <span><i class="far fa-clock"></i> <?= date('g:i A', strtotime($order['order_date'])) ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> Dine-in / Pickup</span>
                            </div>
                        </div>
                        <div class="status-actions-container">
                            <form method="POST" action="update_order_status.php" id="form-<?= $order['id'] ?>">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" onchange="confirmStatusChange(this)" class="status-select status-<?= $order['status'] ?>">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>⏳ Processing</option>
                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>✨ Ready for Guest</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>🚫 Refine/Cancel</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    
                    <div class="order-items-panel">
                        <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item-row">
                            <div class="item-main">
                                <div class="item-qty-badge"><?= $item['quantity'] ?>x</div>
                                <div class="item-details">
                                    <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                                    <span class="item-options">Artisan Selection</span>
                                    <span class="item-price-unit"><?= number_format($item['price'], 0) ?> MMK / unit</span>
                                </div>
                            </div>
                            <span class="item-total-price"><?= number_format($item['price'] * $item['quantity'], 0) ?> MMK</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($order['notes'])): ?>
                    <div class="order-notes-premium">
                        <strong><i class="fas fa-pen-nib"></i> Artisan Notes</strong>
                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="order-footer">
                        <div class="shipping-info">
                            <?php if (isset($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">Includes <?= number_format($order['delivery_fee'], 0) ?> MMK service fee</span>
                            <?php endif; ?>
                        </div>
                        <div class="footer-total-box">
                            <span class="total-label">Final Investment</span>
                            <span class="total-value-main"><?= number_format($order['total_price'], 0) ?></span>
                            <span class="total-currency">MMK</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function confirmStatusChange(selectElement) {
            const orderId = selectElement.form.querySelector('input[name="order_id"]').value;
            const newStatus = selectElement.value;
            const orderIdShort = orderId.substring(0, 8);
            
            let message = '';
            let customerEffect = '';
            
            switch(newStatus) {
                case 'pending':
                    message = `Change order #${orderIdShort} to "Processing"?`;
                    customerEffect = '• Customer will see: "Your order is being prepared"';
                    break;
                case 'completed':
                    message = `Mark order #${orderIdShort} as "Ready"?`;
                    customerEffect = '• Customer will see: "Your order is ready!"\n• Order will show with green checkmark';
                    break;
                case 'cancelled':
                    message = `Cancel order #${orderIdShort}?`;
                    customerEffect = '• Customer will see: "This order was cancelled"\n• Order will show with red X mark\n• ⚠️ This action cannot be undone';
                    break;
            }
            
            const fullMessage = `${message}\n\nCustomer Impact:\n${customerEffect}\n\nContinue?`;
            
            if (confirm(fullMessage)) {
                selectElement.form.submit();
            } else {
                selectElement.selectedIndex = Array.from(selectElement.options).findIndex(option => 
                    option.hasAttribute('selected')
                );
            }
        }
    </script>
</body>
</html>
