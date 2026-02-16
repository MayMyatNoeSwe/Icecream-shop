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
    $stmt = $db->query("SELECT o.*, c.name, c.email 
                       FROM orders o 
                       JOIN customers c ON o.customer_id = c.id 
                       ORDER BY o.order_date DESC");
    $orders = $stmt->fetchAll();
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>📦 All Orders</h1>
            <nav>
                <a href="../index.php">← Back to Shop</a>
                <a href="index.php">📦 Products</a>
                <a href="add_product.php">➕ Add Product</a>
                <a href="orders.php">📋 All Orders</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'status_updated'): ?>
            <div class="success" style="margin-bottom: 20px;">
                ✅ Order status updated successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error" style="margin-bottom: 20px;">
                ❌ <?php 
                    switch($_GET['error']) {
                        case 'invalid_status': echo 'Invalid status selected'; break;
                        case 'update_failed': echo 'Failed to update order status'; break;
                        default: echo 'An error occurred'; break;
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 50px; color: #999;">
                <h2>No orders yet</h2>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-info">Order #<?= substr($order['id'], 0, 8) ?></div>
                        <div class="customer-info"><?= htmlspecialchars($order['name']) ?> (<?= htmlspecialchars($order['email']) ?>)</div>
                        <div class="order-info"><?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></div>
                        <div class="order-info">
                            <?= ($order['order_type'] ?? 'delivery') === 'dine-in' ? '🍽️ Dine-in' : '🚚 Delivery' ?>
                            <?php if (!empty($order['delivery_township'])): ?>
                                - <?= htmlspecialchars($order['delivery_township']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="status-actions">
                        <form method="POST" action="update_order_status.php" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" onchange="confirmStatusChange(this)" class="status-select">
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>⏳ Processing</option>
                                <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>✅ Ready/Delivered</option>
                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Cancel Order</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <div class="order-items">
                    <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <span><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?></span>
                        <span><?= number_format($item['price'] * $item['quantity'], 0) ?> MMK</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($order['notes'])): ?>
                <div class="order-notes">
                    <div class="notes-header">
                        <strong>📝 Customer Notes:</strong>
                    </div>
                    <div class="notes-content">
                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="order-total">
                    Total: <?= number_format($order['total_price'], 0) ?> MMK
                    <?php if (isset($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                        <span style="font-size: 14px; color: #666; font-weight: normal;"> (includes <?= number_format($order['delivery_fee'], 0) ?> MMK delivery)</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
                    message = `Mark order #${orderIdShort} as "Ready/Delivered"?`;
                    customerEffect = '• Customer will see: "Your order is ready for pickup!" (dine-in) or "Your order has been delivered" (delivery)\n• Order will show with green checkmark';
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
                // Reset to original value if cancelled
                selectElement.selectedIndex = Array.from(selectElement.options).findIndex(option => 
                    option.hasAttribute('selected')
                );
            }
        }
    </script>
</body>
</html>
