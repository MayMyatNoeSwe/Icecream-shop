<?php
session_start();

// Strict Admin Auth Check
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Overview Statistics
    $totalRevenue = $db->query("SELECT SUM(total_price) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0;
    $totalExpenses = $db->query("SELECT SUM(amount) FROM expenses")->fetchColumn() ?: 0;
    $totalOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status != 'pending'")->fetchColumn() ?: 0;
    $pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0;
    $totalCustomers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn() ?: 0;
    try {
        $msgPending = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'pending'")->fetchColumn();
        $catPending = $db->query("SELECT COUNT(*) FROM catering_inquiries WHERE status = 'pending'")->fetchColumn();
        $pendingInbox = $msgPending + $catPending;
    } catch (Exception $e) { $pendingInbox = 0; }

    // Low Stock Alert (Threshold 50)
    $lowStockCount = $db->query("SELECT COUNT(*) FROM products WHERE quantity < 50")->fetchColumn();
    $lowStockItems = [];
    if ($lowStockCount > 0) {
        $lowStockItems = $db->query("SELECT name, quantity FROM products WHERE quantity < 50 LIMIT 3")->fetchAll();
    }
    
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
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Component Specific Styles */
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
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
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
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .stat-value {
            font-size: 1.45rem;
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
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Dashboard Overview</h1>
            <div class="admin-profile">
                <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
        
        <?php if ($lowStockCount > 0): ?>
        <div class="panel" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(255, 255, 255, 0.7) 100%); backdrop-filter: blur(15px); border: 1px solid rgba(239, 68, 68, 0.15); border-left: 6px solid var(--danger); margin-bottom: 2.5rem; padding: 1.5rem 2.5rem; border-radius: 24px; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(239, 68, 68, 0.08);">
            <!-- Subtle background element -->
            <div style="position: absolute; right: -20px; top: -20px; font-size: 8rem; color: rgba(239, 68, 68, 0.03); transform: rotate(-15deg); pointer-events: none;">
                <i class="fas fa-box-open"></i>
            </div>
            
            <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 1;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="width: 50px; height: 50px; background: white; color: var(--danger); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; box-shadow: 0 8px 15px rgba(239, 68, 68, 0.1);">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div>
                        <h4 style="color: var(--text-main); font-weight: 800; font-size: 1.1rem; margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif;">Inventory Attention Required</h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; opacity: 0.9;">
                            We've identified <strong style="color: var(--danger);"><?= $lowStockCount ?></strong> premium items currently below the critical threshold of 50 units.
                        </p>
                    </div>
                </div>
                <a href="product.php" class="btn" style="background: linear-gradient(135deg, var(--danger), #ff6b6b); color: white; padding: 12px 24px; font-size: 0.85rem; border-radius: 14px; box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2); transition: all 0.3s ease; border: none; font-weight: 700;">
                    Refill Stock <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
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
                <div class="stat-icon bg-red" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Expense</h3>
                    <div class="stat-value"><?= number_format($totalExpenses, 0) ?> <span style="font-size: 1rem; color: var(--text-muted);">MMK</span></div>
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
                <div class="stat-icon bg-blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Customers</h3>
                    <div class="stat-value"><?= number_format($totalCustomers) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-content">
                    <h3>Pending Inbox</h3>
                    <div class="stat-value"><?= number_format($pendingInbox) ?></div>
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
