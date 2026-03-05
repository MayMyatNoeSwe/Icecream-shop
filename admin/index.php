<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();

// Strict Admin Auth Check
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Overview Statistics
    $totalRevenue = $db->query("SELECT SUM(total_price) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0;
    $totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
    $pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0;
    $totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn() ?: 0;
    
    // Recent Orders
    $recentOrders = $db->query("
        SELECT o.*, COALESCE(u.name, 'Guest') as customer_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ")->fetchAll();
    
    // Chart Data: Last 7 Days Revenue
    $finalDatesArr = [];
    $finalRevenuesArr = [];
    
    // Create an array of the last 7 dates starting from 6 days ago up to today
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $displayDate = date('M d', strtotime($date));
        $finalDatesArr[$date] = $displayDate;
        $finalRevenuesArr[$date] = 0;
    }

    // Improved query using DATE_SUB and CURDATE()
    $chartDataQuery = $db->query("
        SELECT DATE(order_date) as order_day, SUM(total_price) as daily_revenue
        FROM orders 
        WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
        AND status = 'completed'
        GROUP BY DATE(order_date)
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Merge actual data into our pre-filled array
    if ($chartDataQuery) {
        foreach($chartDataQuery as $row) {
            if (isset($finalRevenuesArr[$row['order_day']])) {
                $finalRevenuesArr[$row['order_day']] = (float)$row['daily_revenue'];
            }
        }
    }
    
    // Extract values for JS
    $jsDates = array_values($finalDatesArr);
    $jsRevenues = array_values($finalRevenuesArr);
    
    // Top Products
    $topProducts = $db->query("
        SELECT product_name, SUM(quantity) as sold 
        FROM order_items 
        GROUP BY product_id, product_name 
        ORDER BY sold DESC 
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error Database Connection: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoops Admin Premium Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
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
        }

        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Premium Sidebar Layout */
        .sidebar {
            width: 250px;
            background: var(--surface);
            padding: 1.25rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.02);
            z-index: 10;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.4rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            color: var(--text-main);
            text-decoration: none;
            letter-spacing: -0.02em;
        }
        
        .sidebar-logo i {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .nav-section { margin-bottom: 1.5rem; }
        
        .nav-section-title {
            padding: 0 2rem;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 0.8rem;
            letter-spacing: 1px;
            opacity: 0.7;
        }
        
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
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            background: rgba(108, 93, 252, 0.04);
            color: var(--primary);
            padding-left: 2.25rem;
        }
        
        .nav-link.active {
            background: rgba(108, 93, 252, 0.08);
            color: var(--primary);
            border-left-color: var(--primary);
        }
        
        .nav-link i { width: 22px; text-align: center; font-size: 1.2rem; }
        
        .nav-link .badge {
            margin-left: auto;
            background: var(--warning);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem 2.5rem;
        }
        
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--surface);
            padding: 1.25rem;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(108, 93, 252, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0; width: 100px; height: 100px;
            background: linear-gradient(135deg, rgba(108, 93, 252, 0.1) 0%, rgba(255,255,255,0) 100%);
            border-radius: 0 0 0 100%;
        }
        
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 0.8rem;
        }
        
        .bg-purple { background: rgba(108, 93, 252, 0.1); color: var(--primary); }
        .bg-green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .bg-orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .bg-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        
        .stat-content h3 {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        /* Charts & Lists Area */
        .dashboard-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.8rem;
        }
        
        .panel {
            background: var(--surface);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .panel-title {
            font-size: 1.15rem;
            color: var(--text-main);
            font-weight: 800;
        }
        
        /* Table Styles */
        .premium-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        
        .premium-table th {
            text-align: left;
            padding: 0 1.25rem 0.5rem;
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid rgba(44, 41, 109, 0.05);
        }
        
        .premium-table td {
            padding: 1.25rem;
            background: rgba(241, 239, 233, 0.3);
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .premium-table tr td:first-child { border-radius: 14px 0 0 14px; }
        .premium-table tr td:last-child { border-radius: 0 14px 14px 0; }
        
        .premium-table tr:hover td {
            background: rgba(108, 93, 252, 0.04);
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .status-completed { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .status-cancelled { background: rgba(239, 68, 68, 0.15); color: #dc2626; }

        /* Top Products List */
        .top-product-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.2rem 0;
            border-bottom: 1px solid rgba(44, 41, 109, 0.05);
        }
        
        .top-product-item:last-child { border-bottom: none; }
        
        .tp-info h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.2rem;
        }
        
        .tp-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
        }
        
        .tp-stat {
            background: rgba(108, 93, 252, 0.08);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.85rem;
        }

        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-row { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1.5rem; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    
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
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                    <?php if ($pendingOrders > 0): ?>
                        <span class="badge"><?= $pendingOrders ?></span>
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
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Dashboard Overview</h1>
            <div class="admin-profile">
                <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Revenue</h3>
                    <div class="stat-value"><?= number_format($totalRevenue, 0) ?> <span style="font-size: 1rem; color: var(--text-muted);">MMK</span></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Orders</h3>
                    <div class="stat-value"><?= number_format($totalOrders) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-orange">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>Pending Orders</h3>
                    <div class="stat-value"><?= number_format($pendingOrders) ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Customers</h3>
                    <div class="stat-value"><?= number_format($totalCustomers) ?></div>
                </div>
            </div>
        </div>

        <div class="dashboard-row">
            <!-- Left Chart & Table -->
            <div>
                <div class="panel" style="margin-bottom: 1.8rem;">
                    <div class="panel-header">
                        <h2 class="panel-title">Revenue Last 7 Days</h2>
                    </div>
                    <canvas id="revenueChart" style="width: 100%; height: 300px; display: block;"></canvas>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">Recent Orders</h2>
                        <a href="orders.php" style="color: var(--primary); font-weight: 700; text-decoration: none; font-size: 0.9rem;">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (count($recentOrders) > 0): ?>
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= substr($order['id'], 0, 8) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= number_format($order['total_price'], 0) ?> MMK</td>
                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p style="color: var(--text-muted); text-align: center; padding: 2rem 0; font-weight: 600;">No recent orders</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Sidebar Panel -->
            <div>
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">Top Selling Products</h2>
                    </div>
                    <div class="top-products-list">
                        <?php if (count($topProducts) > 0): ?>
                            <?php foreach($topProducts as $idx => $tp): ?>
                            <div class="top-product-item">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="width: 35px; height: 35px; background: rgba(108, 93, 252, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 800;">
                                        #<?= $idx + 1 ?>
                                    </div>
                                    <div class="tp-info">
                                        <h4><?= htmlspecialchars($tp['product_name']) ?></h4>
                                    </div>
                                </div>
                                <div class="tp-stat">
                                    <?= number_format($tp['sold']) ?> Sold
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); text-align: center; padding: 2rem 0; font-weight: 600;">No product data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Chart Data
        const dates = <?= json_encode($jsDates) ?>;
        const revenues = <?= json_encode($jsRevenues) ?>;
        
        window.addEventListener('load', function() {
            if (typeof Chart === 'undefined') {
                console.error("Chart.js not loaded!");
                return;
            }

            if (dates && dates.length > 0) {
                const ctx = document.getElementById('revenueChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: dates,
                            datasets: [{
                                label: 'Revenue',
                                data: revenues,
                                backgroundColor: '#4a90e2',
                                borderRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            if (value >= 1000) return (value / 1000) + 'k';
                                            return value;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>
