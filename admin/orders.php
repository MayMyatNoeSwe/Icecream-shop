<?php
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
    $stmt = $db->query("SELECT o.*, u.name, u.email 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .sidebar-logo i {
            font-size: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px;
            border-radius: 12px;
        }
        
        .sidebar-nav {
            padding: 1.5rem 0;
        }
        
        .nav-section {
            margin-bottom: 2rem;
        }
        
        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.875rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: #667eea;
        }
        
        .nav-link.active {
            background: rgba(102, 126, 234, 0.15);
            color: white;
            border-left-color: #667eea;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .nav-link .badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Alert Messages */
        .success, .error {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        /* Order Cards */
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .order-info {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .customer-info {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0.5rem 0;
        }
        
        .status-select {
            padding: 0.625rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        
        .status-select:hover {
            border-color: #667eea;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .order-items {
            margin-bottom: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-notes {
            background: #f8fafc;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
        }
        
        .notes-header {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .notes-content {
            color: #475569;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        
        .order-total {
            text-align: right;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            padding-top: 1rem;
            border-top: 2px solid #f1f5f9;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        .empty-state h2 {
            color: #64748b;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .order-header {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
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
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Products</div>
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        <span>All Products</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Other</div>
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-store"></i>
                        <span>View Shop</span>
                    </a>
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
                <h1 class="page-title">Order Management</h1>
            </div>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'status_updated'): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i> Order status updated successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                        switch($_GET['error']) {
                            case 'invalid_status': echo 'Invalid status selected'; break;
                            case 'update_failed': echo 'Failed to update order status'; break;
                            default: echo 'An error occurred'; break;
                        }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>No orders yet</h2>
                    <p style="color: #94a3b8; margin-top: 0.5rem;">Orders will appear here when customers place them</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-info">Order #<?= substr($order['id'], 0, 8) ?></div>
                            <div class="customer-info"><?= htmlspecialchars($order['name']) ?> (<?= htmlspecialchars($order['email']) ?>)</div>
                            <div class="order-info">
                                <i class="far fa-calendar"></i> <?= date('F j, Y g:i A', strtotime($order['order_date'])) ?>
                            </div>
                            <div class="order-info">
                                <i class="fas fa-utensils"></i> Dine-in / Pickup
                            </div>
                        </div>
                        <div class="status-actions">
                            <form method="POST" action="update_order_status.php" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" onchange="confirmStatusChange(this)" class="status-select">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>⏳ Processing</option>
                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>✅ Ready for Pickup</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Cancel Order</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item">
                            <span><strong><?= htmlspecialchars($item['product_name']) ?></strong> × <?= $item['quantity'] ?></span>
                            <span><strong><?= number_format($item['price'] * $item['quantity'], 0) ?> MMK</strong></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($order['notes'])): ?>
                    <div class="order-notes">
                        <div class="notes-header">
                            <i class="fas fa-sticky-note"></i> <strong>Customer Notes:</strong>
                        </div>
                        <div class="notes-content">
                            <?= nl2br(htmlspecialchars($order['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="order-total">
                        Total: <?= number_format($order['total_price'], 0) ?> MMK
                        <?php if (isset($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                            <span style="font-size: 14px; color: #64748b; font-weight: normal;"> (includes <?= number_format($order['delivery_fee'], 0) ?> MMK delivery)</span>
                        <?php endif; ?>
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
