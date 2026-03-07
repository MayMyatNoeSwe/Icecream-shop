<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Database connection for badges if not already available
if (!isset($db)) {
    require_once '../config/database.php';
    $db = Database::getInstance()->getConnection();
}

// Count pending orders for badge
$pendingOrdersCount = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0;

// Count pending inbox items
$pendingInboxCount = 0;
try {
    $msgPending = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'pending'")->fetchColumn() ?: 0;
    $catPending = $db->query("SELECT COUNT(*) FROM catering_inquiries WHERE status = 'pending'")->fetchColumn() ?: 0;
    $pendingInboxCount = $msgPending + $catPending;
} catch (Exception $e) { /* Table might not exist yet */ }
?>

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
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="accounting.php" class="nav-link <?= $current_page == 'accounting.php' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Accounting</span>
            </a>
            <a href="orders.php" class="nav-link <?= $current_page == 'orders.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="badge"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>
            <a href="coupons.php" class="nav-link <?= $current_page == 'coupons.php' ? 'active' : '' ?>">
                <i class="fas fa-ticket-alt"></i>
                <span>Coupons</span>
            </a>
            <a href="contact_messages.php" class="nav-link <?= $current_page == 'contact_messages.php' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i>
                <span>Inbox</span>
                <?php if ($pendingInboxCount > 0): ?>
                    <span class="badge"><?= $pendingInboxCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Products</div>
            <a href="product.php" class="nav-link <?= in_array($current_page, ['product.php', 'add_product.php', 'edit_product.php']) ? 'active' : '' ?>">
                <i class="fas fa-box"></i>
                <span>All Products</span>
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
