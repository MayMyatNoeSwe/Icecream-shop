<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Date Range Filtering
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Get orders with date filter
    $stmt = $db->prepare("SELECT o.*, u.name as user_name, u.email as user_email 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       WHERE DATE(o.order_date) BETWEEN ? AND ?
                       ORDER BY o.order_date DESC");
    $stmt->execute([$startDate, $endDate]);
    $orders = $stmt->fetchAll();
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
    
    // Get statistics
    $totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
    $completedOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn();
    $cancelledOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'")->fetchColumn();
    $todayRevenue = $db->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status = 'completed' AND DATE(order_date) = CURDATE()")->fetchColumn();

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Scoops Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Component Specific Styles */
        :root {
            --primary-soft: rgba(108, 93, 252, 0.08);
            --success-soft: rgba(16, 185, 129, 0.1);
            --warning-soft: rgba(245, 158, 11, 0.1);
            --danger-soft: rgba(239, 68, 68, 0.1);
        }

        /* ═══════════════════════════════════════ */
        /*  MAIN CONTENT                           */
        /* ═══════════════════════════════════════ */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem 2rem;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            padding: 0.8rem 1.5rem;
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--surface);
            padding: 8px 15px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            font-weight: 700;
            font-size: 0.95rem;
        }
        .admin-profile i {
            background: var(--primary);
            color: white;
            width: 32px; height: 32px;
            display: flex; justify-content: center; align-items: center;
            border-radius: 50%;
            font-size: 0.8rem;
        }

        /* ═══════════════════════════════════════ */
        /*  MINI STATS                             */
        /* ═══════════════════════════════════════ */
        .mini-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .mini-stat-card {
            background: var(--surface);
            padding: 1.1rem 1.25rem;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: var(--transition);
            cursor: pointer;
        }
        .mini-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(108, 93, 252, 0.1);
        }

        .mini-stat-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .mini-stat-info h4 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
            margin-bottom: 3px;
        }
        .mini-stat-info span {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .bg-purple { background: rgba(108, 93, 252, 0.1); color: var(--primary); }
        .bg-orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .bg-green  { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .bg-red    { background: rgba(239, 68, 68, 0.1); color: var(--danger); }

        /* ═══════════════════════════════════════ */
        /*  FILTER TABS                            */
        /* ═══════════════════════════════════════ */
        .orders-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-tabs {
            display: flex;
            gap: 6px;
            background: var(--surface);
            padding: 5px;
            border-radius: 14px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .filter-tab {
            padding: 8px 18px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            border: none;
            background: transparent;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .filter-tab:hover { background: rgba(108, 93, 252, 0.05); color: var(--primary); }
        .filter-tab.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(108, 93, 252, 0.3);
        }

        .filter-count {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 1px 7px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.25);
        }
        .filter-tab:not(.active) .filter-count {
            background: rgba(108, 93, 252, 0.08);
            color: var(--primary);
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface);
            padding: 8px 16px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
            min-width: 260px;
        }
        .search-box i { color: var(--text-muted); font-size: 0.9rem; }
        .search-box input {
            border: none;
            outline: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            width: 100%;
        }
        .search-box input::placeholder { color: var(--text-muted); opacity: 0.6; }

        /* ═══════════════════════════════════════ */
        /*  ORDER CARDS                            */
        /* ═══════════════════════════════════════ */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-card {
            background: var(--surface);
            border-radius: 18px;
            padding: 0;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition);
            overflow: visible;
            animation: fadeSlideUp 0.4s ease forwards;
            opacity: 0;
            position: relative;
        }
        .order-card:hover {
            box-shadow: 0 12px 35px rgba(108, 93, 252, 0.1);
            transform: translateY(-2px);
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .order-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(44, 41, 109, 0.04);
            border-radius: 18px 18px 0 0;
            background: var(--surface);
        }

        .order-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .order-avatar {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .order-info h3 {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 2px;
        }
        .order-info-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        .order-info-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .order-info-meta i { font-size: 0.65rem; }

        .order-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .order-total {
            text-align: right;
        }
        .order-total-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .order-total-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-main);
        }
        .order-total-currency {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-left: 2px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            position: relative;
        }
        .status-badge:hover {
            transform: scale(1.05);
        }
        .status-badge i { font-size: 0.6rem; }

        .status-pending {
            background: var(--warning-soft);
            color: #d97706;
            border-color: rgba(245, 158, 11, 0.2);
        }
        .status-completed {
            background: var(--success-soft);
            color: #059669;
            border-color: rgba(16, 185, 129, 0.2);
        }
        .status-cancelled {
            background: var(--danger-soft);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.2);
        }

        /* Order Items Row */
        .order-items-row {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            gap: 8px;
            flex-wrap: wrap;
            background: rgba(108, 93, 252, 0.015);
            border-radius: 0 0 18px 18px;
        }

        .item-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: white;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-main);
            border: 1px solid rgba(44, 41, 109, 0.06);
        }
        .item-chip-qty {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 2px 6px;
            border-radius: 5px;
            font-size: 0.68rem;
            font-weight: 800;
        }
        .item-chip-price {
            color: var(--text-muted);
            font-size: 0.7rem;
            font-weight: 600;
        }

        .items-more {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(108, 93, 252, 0.06);
        }

        /* Order Footer */
        .order-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 1.5rem;
            border-top: 1px solid rgba(44, 41, 109, 0.04);
        }

        .order-notes-inline {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #856404;
            background: #fffbeb;
            padding: 5px 12px;
            border-radius: 8px;
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .order-notes-inline i { flex-shrink: 0; }

        .action-buttons {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            width: 34px; height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(44, 41, 109, 0.08);
            background: white;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            color: var(--text-muted);
            transition: var(--transition);
        }
        .action-btn:hover {
            background: var(--primary-soft);
            color: var(--primary);
            border-color: rgba(108, 93, 252, 0.15);
            transform: scale(1.1);
        }
        .action-btn.btn-ready:hover { background: var(--success-soft); color: var(--success); border-color: rgba(16,185,129,0.2); }
        .action-btn.btn-cancel:hover { background: var(--danger-soft); color: var(--danger); border-color: rgba(239,68,68,0.2); }
        .action-btn.btn-revert:hover { background: var(--warning-soft); color: var(--warning); border-color: rgba(245,158,11,0.2); }

        /* Status Action Dropdown */
        .status-dropdown-wrap {
            position: relative;
            z-index: 60;
        }

        .status-dropdown {
            position: fixed;
            background: white;
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 20px 60px rgba(44, 41, 109, 0.25), 0 0 0 1px rgba(44, 41, 109, 0.06);
            z-index: 9999;
            display: none;
            min-width: 240px;
            animation: dropdownIn 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .status-dropdown.show { display: block; }

        @keyframes dropdownIn {
            from { opacity: 0; transform: translateY(-8px) scale(0.92); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-main);
            cursor: pointer;
            transition: var(--transition);
            border: none;
            background: none;
            width: 100%;
            font-family: inherit;
            text-align: left;
        }
        .dropdown-item:hover { background: rgba(108, 93, 252, 0.06); }
        .dd-pending:hover { background: var(--warning-soft); }
        .dd-completed:hover { background: var(--success-soft); }
        .dd-cancelled:hover { background: var(--danger-soft); }

        .dropdown-item .dd-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .dd-pending .dd-icon { background: var(--warning-soft); color: var(--warning); }
        .dd-completed .dd-icon { background: var(--success-soft); color: var(--success); }
        .dd-cancelled .dd-icon { background: var(--danger-soft); color: var(--danger); }

        .dropdown-item.dd-active {
            background: var(--primary-soft);
            color: var(--primary);
        }
        .dropdown-divider {
            height: 1px;
            background: rgba(44, 41, 109, 0.08);
            margin: 6px 12px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }
        .empty-state-icon {
            width: 80px; height: 80px;
            margin: 0 auto 1.5rem;
            background: var(--primary-soft);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            color: var(--primary);
        }
        .empty-state h2 { font-size: 1.3rem; font-weight: 800; margin-bottom: 0.4rem; }
        .empty-state p { color: var(--text-muted); font-weight: 500; font-size: 0.9rem; }

        /* Toast notification */
        .toast-msg {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 14px 24px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 999;
            animation: toastIn 0.4s ease, toastOut 0.4s ease 3s forwards;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .toast-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .toast-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(40px); }
        }

        .hidden { display: none !important; }

        /* Responsive */
        @media (max-width: 1200px) {
            .mini-stats { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .mini-stats { grid-template-columns: 1fr; }
            .order-card-top { flex-direction: column; gap: 12px; align-items: flex-start; }
            .order-right { width: 100%; justify-content: space-between; }
            .orders-toolbar { flex-direction: column; }
            .search-box { min-width: 100%; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h1 class="page-title">Order Management</h1>
                <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-top: 4px;">Viewing: <?= date('M d', strtotime($startDate)) ?> - <?= date('M d, Y', strtotime($endDate)) ?></p>
            </div>
            
            <form action="orders.php" method="GET" class="admin-profile" style="padding: 10px 20px; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-alt" style="background: none; color: var(--primary); width: auto; font-size: 1rem;"></i>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="border: none; outline: none; font-family: inherit; font-size: 0.85rem; font-weight: 700; color: var(--text-main);">
                    <span style="color: var(--text-muted); font-weight: 800; font-size: 0.7rem;">TO</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" style="border: none; outline: none; font-family: inherit; font-size: 0.85rem; font-weight: 700; color: var(--text-main);">
                    <button type="submit" style="background: var(--primary); color: white; border: none; padding: 5px 12px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.8rem; margin-left: 5px;">Filter</button>
                </div>
            </form>

            <div class="admin-profile" style="display: none;"> <!-- Hidden backup -->
                <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        <!-- Mini Stats -->
        <div class="mini-stats">
            <div class="mini-stat-card" onclick="filterOrders('all')">
                <div class="mini-stat-icon bg-purple">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="mini-stat-info">
                    <h4><?= number_format($totalOrders) ?></h4>
                    <span>Total Orders</span>
                </div>
            </div>
            <div class="mini-stat-card" onclick="filterOrders('pending')">
                <div class="mini-stat-icon bg-orange">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="mini-stat-info">
                    <h4><?= number_format($pendingOrders) ?></h4>
                    <span>Processing</span>
                </div>
            </div>
            <div class="mini-stat-card" onclick="filterOrders('completed')">
                <div class="mini-stat-icon bg-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <h4><?= number_format($completedOrders) ?></h4>
                    <span>Completed</span>
                </div>
            </div>
            <div class="mini-stat-card" onclick="filterOrders('cancelled')">
                <div class="mini-stat-icon bg-red">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="mini-stat-info">
                    <h4><?= number_format($cancelledOrders) ?></h4>
                    <span>Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Toolbar: Filters + Search -->
        <div class="orders-toolbar">
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all" onclick="filterOrders('all')">
                    All <span class="filter-count"><?= $totalOrders ?></span>
                </button>
                <button class="filter-tab" data-filter="pending" onclick="filterOrders('pending')">
                    <i class="fas fa-hourglass-half"></i> Processing <span class="filter-count"><?= $pendingOrders ?></span>
                </button>
                <button class="filter-tab" data-filter="completed" onclick="filterOrders('completed')">
                    <i class="fas fa-check-circle"></i> Completed <span class="filter-count"><?= $completedOrders ?></span>
                </button>
                <button class="filter-tab" data-filter="cancelled" onclick="filterOrders('cancelled')">
                    <i class="fas fa-ban"></i> Cancelled <span class="filter-count"><?= $cancelledOrders ?></span>
                </button>
            </div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search orders, customers..." oninput="searchOrders(this.value)">
            </div>
        </div>

        <!-- Orders List -->
        <div class="orders-list" id="ordersList">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h2>No Orders Yet</h2>
                    <p>Orders will appear here as customers place them.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $idx => $order): 
                    $initials = strtoupper(substr($order['user_name'] ?? 'G', 0, 1));
                    $avatarColors = ['#6c5dfc','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6'];
                    $avatarColor = $avatarColors[$idx % count($avatarColors)];
                    $itemCount = count($order['items']);
                ?>
                <div class="order-card" data-status="<?= $order['status'] ?>" data-customer="<?= htmlspecialchars(strtolower($order['user_name'] ?? '')) ?>" data-orderid="<?= $order['id'] ?>" style="animation-delay: <?= $idx * 0.05 ?>s">
                    <!-- Top Row: Customer + Status + Total -->
                    <div class="order-card-top">
                        <div class="order-left">
                            <div class="order-avatar" style="background: <?= $avatarColor ?>15; color: <?= $avatarColor ?>">
                                <?= $initials ?>
                            </div>
                            <div class="order-info">
                                <h3><?= htmlspecialchars($order['user_name'] ?? 'Guest') ?></h3>
                                <div class="order-info-meta">
                                    <span><i class="fas fa-hashtag"></i> <?= substr($order['id'], 0, 8) ?></span>
                                    <span><i class="far fa-calendar"></i> <?= date('M j, Y', strtotime($order['order_date'])) ?></span>
                                    <span><i class="far fa-clock"></i> <?= date('g:i A', strtotime($order['order_date'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="order-right">
                            <div class="order-total">
                                <div class="order-total-label">Total</div>
                                <div>
                                    <span class="order-total-value"><?= number_format($order['total_price'], 0) ?></span>
                                    <span class="order-total-currency">MMK</span>
                                </div>
                            </div>
                            <div class="status-dropdown-wrap">
                                <div class="status-badge status-<?= $order['status'] ?>" onclick="openStatusPicker('<?= $order['id'] ?>', '<?= $order['status'] ?>', '<?= htmlspecialchars(substr($order['id'], 0, 8)) ?>')">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <i class="fas fa-hourglass-half"></i> Processing
                                    <?php elseif ($order['status'] === 'completed'): ?>
                                        <i class="fas fa-check-circle"></i> Completed
                                    <?php else: ?>
                                        <i class="fas fa-ban"></i> Cancelled
                                    <?php endif; ?>
                                    <i class="fas fa-chevron-down" style="font-size: 0.55rem; margin-left: 2px;"></i>
                                </div>
                                <form method="POST" action="update_order_status.php" id="form-<?= $order['id'] ?>" style="display:none;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="status" value="">
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Items Row -->
                    <div class="order-items-row">
                        <?php foreach (array_slice($order['items'], 0, 3) as $item): ?>
                        <div class="item-chip">
                            <span class="item-chip-qty"><?= $item['quantity'] ?>x</span>
                            <?= htmlspecialchars($item['product_name']) ?>
                            <span class="item-chip-price"><?= number_format($item['price'] * $item['quantity'], 0) ?> MMK</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($itemCount > 3): ?>
                            <span class="items-more">+<?= $itemCount - 3 ?> more</span>
                        <?php endif; ?>
                    </div>

                    <!-- Footer -->
                    <?php if (!empty($order['notes'])): ?>
                    <div class="order-card-footer">
                        <div class="order-notes-inline">
                            <i class="fas fa-sticky-note"></i>
                            <?= htmlspecialchars(substr($order['notes'], 0, 80)) ?><?= strlen($order['notes']) > 80 ? '...' : '' ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Toast Messages -->
    <?php if (isset($_GET['success']) && $_GET['success'] === 'status_updated'): ?>
        <div class="toast-msg toast-success">
            <i class="fas fa-check-circle"></i>
            Order status updated successfully!
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="toast-msg toast-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php
                switch($_GET['error']) {
                    case 'invalid_status': echo 'Invalid status selected'; break;
                    case 'update_failed': echo 'Failed to update status'; break;
                    default: echo 'An error occurred'; break;
                }
            ?>
        </div>
    <?php endif; ?>

    <script>
        // ═══════════════════════════════════════
        //  STATUS PICKER WITH SWEETALERT2
        // ═══════════════════════════════════════
        function openStatusPicker(orderId, currentStatus, orderShort) {
            const statuses = [
                { key: 'pending', label: 'Processing', icon: 'fa-hourglass-half', color: '#f59e0b', bgColor: 'rgba(245,158,11,0.08)', desc: 'Order is being prepared' },
                { key: 'completed', label: 'Ready for Guest', icon: 'fa-check-circle', color: '#10b981', bgColor: 'rgba(16,185,129,0.08)', desc: 'Order is ready for pickup' },
                { key: 'cancelled', label: 'Cancel Order', icon: 'fa-ban', color: '#ef4444', bgColor: 'rgba(239,68,68,0.08)', desc: 'Cancel this order' }
            ];

            let buttonsHtml = statuses.map(s => {
                const isActive = s.key === currentStatus;
                return `<button class="swal-status-btn ${isActive ? 'swal-status-active' : ''}" 
                    data-status="${s.key}" 
                    style="display:flex; align-items:center; gap:14px; width:100%; padding:14px 18px; border:2px solid ${isActive ? s.color : 'transparent'}; border-radius:14px; background:${isActive ? s.bgColor : '#f8f8fc'}; cursor:pointer; font-family:inherit; margin-bottom:8px; transition:all 0.2s ease;"
                    onmouseover="this.style.background='${s.bgColor}'; this.style.borderColor='${s.color}'"
                    onmouseout="this.style.background='${isActive ? s.bgColor : '#f8f8fc'}'; this.style.borderColor='${isActive ? s.color : 'transparent'}'">
                    <span style="width:42px; height:42px; border-radius:12px; background:${s.bgColor}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fas ${s.icon}" style="font-size:1rem; color:${s.color}"></i>
                    </span>
                    <span style="text-align:left;">
                        <span style="display:block; font-size:0.95rem; font-weight:800; color:#2c296d;">${s.label}</span>
                        <span style="display:block; font-size:0.75rem; font-weight:600; color:#6b6b8d; margin-top:2px;">${s.desc}</span>
                    </span>
                    ${isActive ? '<span style="margin-left:auto; font-size:0.7rem; font-weight:800; color:' + s.color + '; text-transform:uppercase; letter-spacing:0.5px;">Current</span>' : ''}
                </button>`;
            }).join('');

            Swal.fire({
                title: 'Update Status',
                html: `<div style="text-align:left; margin-top:8px;">
                    <p style="font-size:0.82rem; color:#6b6b8d; font-weight:600; margin-bottom:16px;">Order <strong style="color:#2c296d">#${orderShort}</strong> — Choose a new status:</p>
                    <div id="swalStatusButtons">${buttonsHtml}</div>
                </div>`,
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    popup: 'swal-popup-custom',
                    title: 'swal-title-custom'
                },
                backdrop: 'rgba(44, 41, 109, 0.25)',
                didOpen: () => {
                    document.querySelectorAll('.swal-status-btn').forEach(btn => {
                        btn.addEventListener('click', () => {
                            const newStatus = btn.dataset.status;
                            if (newStatus === currentStatus) {
                                Swal.close();
                                return;
                            }
                            Swal.close();
                            confirmStatusChange(orderId, newStatus, orderShort);
                        });
                    });
                }
            });
        }

        function confirmStatusChange(orderId, newStatus, orderShort) {
            let config = {};
            switch(newStatus) {
                case 'pending':
                    config = {
                        title: 'Set to Processing?',
                        html: `<div style="text-align:left; font-size:0.9rem; color:#6b6b8d;">
                            <p style="margin-bottom:8px;">Order <strong>#${orderShort}</strong> will be marked as processing.</p>
                            <p style="font-size:0.8rem; color:#d97706;">⏳ Customer will see "Your order is being prepared"</p>
                        </div>`,
                        iconColor: '#f59e0b',
                        icon: 'info',
                        confirmButtonText: 'Set Processing',
                        confirmButtonColor: '#f59e0b'
                    };
                    break;
                case 'completed':
                    config = {
                        title: 'Mark as Ready?',
                        html: `<div style="text-align:left; font-size:0.9rem; color:#6b6b8d;">
                            <p style="margin-bottom:8px;">Order <strong>#${orderShort}</strong> will be marked as ready for guest.</p>
                            <p style="font-size:0.8rem; color:#059669;">✅ Customer will see "Your order is ready!"</p>
                        </div>`,
                        iconColor: '#10b981',
                        icon: 'success',
                        confirmButtonText: 'Mark Ready',
                        confirmButtonColor: '#10b981'
                    };
                    break;
                case 'cancelled':
                    config = {
                        title: 'Cancel This Order?',
                        html: `<div style="text-align:left; font-size:0.9rem; color:#6b6b8d;">
                            <p style="margin-bottom:8px;">Order <strong>#${orderShort}</strong> will be cancelled.</p>
                            <p style="font-size:0.8rem; color:#dc2626;">⚠️ Customer will see "This order was cancelled"</p>
                        </div>`,
                        iconColor: '#ef4444',
                        icon: 'warning',
                        confirmButtonText: 'Cancel Order',
                        confirmButtonColor: '#ef4444'
                    };
                    break;
            }

            Swal.fire({
                ...config,
                showCancelButton: true,
                cancelButtonText: 'Go Back',
                cancelButtonColor: '#6b6b8d',
                reverseButtons: true,
                customClass: {
                    popup: 'swal-popup-custom',
                    title: 'swal-title-custom'
                },
                backdrop: 'rgba(44, 41, 109, 0.25)'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('form-' + orderId);
                    form.querySelector('input[name="status"]').value = newStatus;
                    form.submit();
                }
            });
        }

        // ═══════════════════════════════════════
        //  FILTER ORDERS
        // ═══════════════════════════════════════
        function filterOrders(status) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.filter === status);
            });

            // Filter cards
            document.querySelectorAll('.order-card').forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // ═══════════════════════════════════════
        //  SEARCH ORDERS
        // ═══════════════════════════════════════
        function searchOrders(query) {
            query = query.toLowerCase().trim();
            document.querySelectorAll('.order-card').forEach(card => {
                const customer = card.dataset.customer || '';
                const orderId = card.dataset.orderid.toLowerCase();
                const items = card.querySelector('.order-items-row')?.textContent.toLowerCase() || '';
                const visible = customer.includes(query) || orderId.includes(query) || items.includes(query);
                card.classList.toggle('hidden', !visible);
            });

            // Reset filter tabs
            if (query) {
                document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
            } else {
                filterOrders('all');
            }
        }

        // Auto-hide toasts
        setTimeout(() => {
            document.querySelectorAll('.toast-msg').forEach(t => t.remove());
        }, 3500);
    </script>

    <style>
        .swal-popup-custom {
            border-radius: 20px !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
        .swal-title-custom {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1.3rem !important;
            font-weight: 800 !important;
            color: #2c296d !important;
        }
    </style>
</body>
</html>
