<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();

// Strict Admin Auth Check
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$success_msg = '';
$error_msg = '';

try {
    $db = Database::getInstance()->getConnection();
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'add_expense') {
            $amount = floatval($_POST['amount']);
            $description = trim($_POST['description']);
            $category = $_POST['category'];
            $expense_date = $_POST['expense_date'];
            
            if ($amount > 0 && !empty($description) && !empty($expense_date)) {
                $stmt = $db->prepare("INSERT INTO expenses (amount, description, category, expense_date, admin_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$amount, $description, $category, $expense_date, $_SESSION['admin_id'] ?? null]);
                $success_msg = "Expense added successfully.";
            } else {
                $error_msg = "Please fill all required fields.";
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_expense') {
            $id = intval($_POST['expense_id']);
            $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            $success_msg = "Expense deleted successfully.";
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_expense') {
            $id = intval($_POST['expense_id']);
            $amount = floatval($_POST['amount']);
            $description = trim($_POST['description']);
            $category = $_POST['category'];
            $expense_date = $_POST['expense_date'];
            
            if ($id > 0 && $amount > 0 && !empty($description) && !empty($expense_date)) {
                $stmt = $db->prepare("UPDATE expenses SET amount = ?, description = ?, category = ?, expense_date = ? WHERE id = ?");
                $stmt->execute([$amount, $description, $category, $expense_date, $id]);
                $success_msg = "Expense updated successfully.";
            } else {
                $error_msg = "Please fill all required fields.";
            }
        }
    }
    
    // Calculate Summary Stats
    // 1. Total Revenue (Completed Orders sum)
    $totalRevenue = $db->query("SELECT SUM(total_price) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0;
    
    // 2. Total Expenses
    $totalExpenses = $db->query("SELECT SUM(amount) FROM expenses")->fetchColumn() ?: 0;
    
    // 3. Net Profit
    $netProfit = $totalRevenue - $totalExpenses;
    
    // 4. Monthly Stats (Current Month)
    $currentMonth = date('Y-m');
    $monthlyRevenue = $db->query("SELECT SUM(total_price) FROM orders WHERE status = 'completed' AND DATE_FORMAT(order_date, '%Y-%m') = '$currentMonth'")->fetchColumn() ?: 0;
    $monthlyExpenses = $db->query("SELECT SUM(amount) FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$currentMonth'")->fetchColumn() ?: 0;
    $monthlyProfit = $monthlyRevenue - $monthlyExpenses;
    
    // Get all expenses for datatable
    $recentExpenses = $db->query("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC")->fetchAll();
    
    // Custom Date Range for Chart
    $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 days'));
    $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    // Chart data
    $finalDatesArr = [];
    $dailyRevenuesArr = [];
    $dailyExpensesArr = [];
    
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    
    // Safety break for huge ranges
    $diffDays = $currentDate->diff($endDateObj)->days;
    if ($diffDays > 90) { 
        $currentDate = (clone $endDateObj)->modify('-90 days'); 
        $startDate = $currentDate->format('Y-m-d');
    }

    while ($currentDate <= $endDateObj) {
        $dateStr = $currentDate->format('Y-m-d');
        $displayDate = $currentDate->format('M d');
        
        $finalDatesArr[$dateStr] = $displayDate;
        $dailyRevenuesArr[$dateStr] = 0;
        $dailyExpensesArr[$dateStr] = 0;
        
        $currentDate->modify('+1 day');
    }
    
    // Revenue based on custom date range
    $chartRevQuery = $db->prepare("
        SELECT DATE(order_date) as day, SUM(total_price) as total
        FROM orders 
        WHERE DATE(order_date) >= ? AND DATE(order_date) <= ? AND status = 'completed'
        GROUP BY DATE(order_date)
    ");
    $chartRevQuery->execute([$startDate, $endDate]);
    $revResults = $chartRevQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if ($revResults) {
        foreach($revResults as $row) {
            if (isset($dailyRevenuesArr[$row['day']])) {
                $dailyRevenuesArr[$row['day']] = (float)$row['total'];
            }
        }
    }
    
    // Expenses based on custom date range
    $chartExpQuery = $db->prepare("
        SELECT expense_date as day, SUM(amount) as total
        FROM expenses 
        WHERE expense_date >= ? AND expense_date <= ?
        GROUP BY expense_date
    ");
    $chartExpQuery->execute([$startDate, $endDate]);
    $expResults = $chartExpQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if ($expResults) {
        foreach($expResults as $row) {
            if (isset($dailyExpensesArr[$row['day']])) {
                $dailyExpensesArr[$row['day']] = (float)$row['total'];
            }
        }
    }
    
    $jsDates = array_values($finalDatesArr);
    $jsRevenues = array_values($dailyRevenuesArr);
    $jsExpenses = array_values($dailyExpensesArr);

} catch (Exception $e) {
    die("Error Database Connection: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting & Finance Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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

        /* Sidebar Styles (Same as index.php) */
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

        /* Main Content */
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(108, 93, 252, 0.1);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1.2rem;
        }
        
        .bg-green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .bg-red { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .bg-purple { background: rgba(108, 93, 252, 0.1); color: var(--primary); }
        
        .stat-content h3 {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 0.2rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .stat-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 5px;
        }

        /* Dashboard Row */
        .dashboard-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.8rem;
            margin-bottom: 2rem;
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

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn:hover { background: #5a4bdf; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(108, 93, 252, 0.3); }
        
        .btn-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; }
        .btn-danger:hover { background: var(--danger); color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.2); }

        /* Form Controls */
        .form-group { margin-bottom: 1.2rem; }
        .form-label { display: block; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; font-size: 0.9rem; }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(44, 41, 109, 0.1);
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--text-main);
            background: rgba(241, 239, 233, 0.3);
            transition: var(--transition);
        }
        .form-control:focus { outline: none; border-color: var(--primary); background: var(--surface); box-shadow: 0 0 0 4px rgba(108, 93, 252, 0.1); }
        
        /* Table Styles */
        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .premium-table th { text-align: left; padding: 0 1rem 0.5rem; color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid rgba(44, 41, 109, 0.05); }
        .premium-table td { padding: 1rem; background: rgba(241, 239, 233, 0.3); color: var(--text-main); font-size: 0.95rem; font-weight: 600; }
        .premium-table tr td:first-child { border-radius: 12px 0 0 12px; }
        .premium-table tr td:last-child { border-radius: 0 12px 12px 0; }
        .premium-table tr:hover td { background: rgba(108, 93, 252, 0.04); }
        
        .cat-badge { padding: 5px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .cat-supplies { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
        .cat-salary { background: rgba(139, 92, 246, 0.15); color: #7c3aed; }
        .cat-utilities { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .cat-rent { background: rgba(236, 72, 153, 0.15); color: #db2777; }
        .cat-other { background: rgba(107, 114, 128, 0.15); color: #4b5563; }

        .form-panel {
            background: var(--surface);
            border-radius: 20px;
            padding: 1.8rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        /* Modal Styles */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(44, 41, 109, 0.4); backdrop-filter: blur(8px);
            overflow-y: auto; padding: 20px 0;
            align-items: center; justify-content: center;
        }
        .modal-content {
            background: white; margin: auto; padding: 0; border-radius: 24px;
            width: 90%; max-width: 600px; box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            border: 1px solid rgba(255, 255, 255, 0.8); overflow: hidden;
            animation: modalSlideUp 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            position: relative;
        }
        @keyframes modalSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 1.25rem 2rem; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 1.4rem; color: var(--text-main); font-weight: 800; }
        .close { font-size: 1.5rem; font-weight: 700; color: var(--text-muted); cursor: pointer; transition: 0.2s; padding: 5px; }
        .close:hover { color: var(--danger); transform: rotate(90deg); }
        .modal-body { padding: 2rem; }
        .btn-edit-action { background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; border: none; cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; justify-content: center; margin-right: 5px; }
        .btn-edit-action:hover { background: #3b82f6; color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.2); }

        /* DataTables Custom Styles */
        .dataTables_wrapper { padding: 0.5rem 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        .dataTables_filter input { border: 1px solid rgba(44, 41, 109, 0.2); border-radius: 12px; padding: 6px 12px; margin-left: 8px; outline: none; }
        .dataTables_filter input:focus { border-color: var(--primary); }
        .dataTables_length select { border-radius: 8px; padding: 4px; border: 1px solid rgba(44, 41, 109, 0.2); outline: none; }
        table.dataTable.no-footer { border-bottom: none; }
        table.dataTable { border-collapse: separate !important; border-spacing: 0 8px; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary); color: white !important; border: none; border-radius: 8px; font-weight: 700;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 8px; margin: 0 4px; border: none; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: rgba(108, 93, 252, 0.1); color: var(--primary) !important; border: none; }
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
                <a href="index.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="accounting.php" class="nav-link active">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Accounting</span>
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
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
            <h1 class="page-title">Accounting & Finance</h1>
            <div style="font-weight: 700; color: var(--text-muted); background: var(--surface); padding: 8px 15px; border-radius: 20px; box-shadow: var(--card-shadow);">
                <i class="far fa-calendar-alt"></i> <?= date('F d, Y') ?>
            </div>
        </div>

        <?php if($success_msg): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?= $success_msg ?>',
                    showConfirmButton: false,
                    timer: 2000,
                    customClass: { popup: 'premium-popup' }
                });
            </script>
        <?php endif; ?>

        <?php if($error_msg): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?= $error_msg ?>',
                    customClass: { popup: 'premium-popup' }
                });
            </script>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-green">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Income (Gross)</h3>
                    <div class="stat-value"><?= number_format($totalRevenue, 0) ?> MMK</div>
                    <div class="stat-subtitle">Monthly: <?= number_format($monthlyRevenue, 0) ?> MMK</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-red">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="stat-content">
                    <h3>Total Expenses</h3>
                    <div class="stat-value"><?= number_format($totalExpenses, 0) ?> MMK</div>
                    <div class="stat-subtitle">Monthly: <?= number_format($monthlyExpenses, 0) ?> MMK</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon bg-purple">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="stat-content">
                    <h3>Net Profit</h3>
                    <div class="stat-value" style="color: <?= $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                        <?= number_format($netProfit, 0) ?> MMK
                    </div>
                    <div class="stat-subtitle">Monthly: <?= number_format($monthlyProfit, 0) ?> MMK</div>
                </div>
            </div>
        </div>

        <div class="dashboard-row">
            <!-- Chart Area -->
            <div class="panel">
                <div class="panel-header" style="flex-wrap: wrap; gap: 15px;">
                    <h2 class="panel-title">Cash Flow (<?= $startDate === date('Y-m-d', strtotime('-6 days')) && $endDate === date('Y-m-d') ? 'Last 7 Days' : date('M d', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate)) ?>)</h2>
                    
                    <form action="accounting.php" method="GET" style="display: flex; gap: 8px; align-items: center;">
                        <input type="date" name="start_date" class="form-control" style="padding: 6px 10px; height: auto; min-width: 130px; border-radius: 8px;" value="<?= htmlspecialchars($startDate) ?>" required>
                        <span style="color: var(--text-muted); font-weight: 700; font-size: 0.8rem;">TO</span>
                        <input type="date" name="end_date" class="form-control" style="padding: 6px 10px; height: auto; min-width: 130px; border-radius: 8px;" value="<?= htmlspecialchars($endDate) ?>" required>
                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px; height: auto; border-radius: 8px; font-size: 0.85rem;"><i class="fas fa-filter"></i> View</button>
                    </form>
                </div>
                <canvas id="financeChart" style="width: 100%; height: 320px;"></canvas>
            </div>

            <!-- Add Expense Form -->
            <div class="form-panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> Record Expense</h2>
                </div>
                <form action="accounting.php" method="POST">
                    <input type="hidden" name="action" value="add_expense">
                    
                    <div class="form-group">
                        <label class="form-label">Expense Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Monthly Rent, Flour Supply..." required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Amount (MMK)</label>
                            <input type="number" name="amount" class="form-control" placeholder="0" min="1" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="supplies">Ingredients & Supplies</option>
                            <option value="salary">Staff Salary</option>
                            <option value="utilities">Utilities (Water, Electricity)</option>
                            <option value="rent">Shop Rent</option>
                            <option value="other">Other Expenses</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 10px;">
                        Add Expense Record
                    </button>
                </form>
            </div>
        </div>

        <!-- Expense Ledger -->
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Recent Expenses Ledger</h2>
            </div>
            
            <?php if(count($recentExpenses) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="premium-table" id="expensesTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentExpenses as $exp): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($exp['expense_date'])) ?></td>
                            <td style="font-weight: 700;"><?= htmlspecialchars($exp['description']) ?></td>
                            <td>
                                <span class="cat-badge cat-<?= $exp['category'] ?>">
                                    <?= ucfirst($exp['category']) ?>
                                </span>
                            </td>
                            <td style="color: var(--danger); font-weight: 800;">
                                -<?= number_format($exp['amount'], 0) ?> MMK
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn-edit-action" title="Edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($exp)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="accounting.php" method="POST" class="delete-expense-form" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_expense">
                                    <input type="hidden" name="expense_id" value="<?= $exp['id'] ?>">
                                    <button type="submit" class="btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 800; font-size: 1.1rem; color: var(--text-main); padding-right: 20px;">Subtotal Amount (Filtered):</td>
                            <td colspan="2" style="font-weight: 800; font-size: 1.2rem; color: var(--danger);" id="tableTotalAmount">0 MMK</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem 0; color: var(--text-muted);">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p style="font-weight: 600;">No expenses recorded yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Expense Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-edit" style="color: var(--primary);"></i> Edit Expense</h2>
                    <span class="close" onclick="closeEditModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form action="accounting.php" method="POST" id="editExpenseForm">
                        <input type="hidden" name="action" value="edit_expense">
                        <input type="hidden" name="expense_id" id="edit_expense_id">
                        
                        <div class="form-group">
                            <label class="form-label">Expense Description</label>
                            <input type="text" name="description" id="edit_description" class="form-control" required>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label">Amount (MMK)</label>
                                <input type="number" name="amount" id="edit_amount" class="form-control" min="1" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date</label>
                                <input type="date" name="expense_date" id="edit_expense_date" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" id="edit_category" class="form-control" required>
                                <option value="supplies">Ingredients & Supplies</option>
                                <option value="salary">Staff Salary</option>
                                <option value="utilities">Utilities (Water, Electricity)</option>
                                <option value="rent">Shop Rent</option>
                                <option value="other">Other Expenses</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn" style="width: 100%; justify-content: center; margin-top: 10px;">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <script>
        const dates = <?= json_encode($jsDates) ?>;
        const revenues = <?= json_encode($jsRevenues) ?>;
        const expenses = <?= json_encode($jsExpenses) ?>;
        
        window.addEventListener('load', function() {
            if (typeof Chart === 'undefined') return;

            if (dates && dates.length > 0) {
                const ctx = document.getElementById('financeChart');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: revenues,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 3
                            },
                            {
                                label: 'Expenses',
                                data: expenses,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { usePointStyle: true, boxWidth: 8, font: { family: "'Plus Jakarta Sans', sans-serif" } }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.03)' },
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000) return (value / 1000) + 'k';
                                        return value;
                                    }
                                }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            document.querySelectorAll('.delete-expense-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Delete Expense?',
                        text: 'Are you sure you want to delete this expense record?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b6b8d',
                        confirmButtonText: '<i class=\"fas fa-trash\"></i> Yes, delete it!',
                        customClass: { popup: 'premium-popup' }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });

        // Modal Functions
        const modal = document.getElementById('editModal');
        
        function openEditModal(expense) {
            document.getElementById('edit_expense_id').value = expense.id;
            document.getElementById('edit_description').value = expense.description;
            document.getElementById('edit_amount').value = expense.amount;
            document.getElementById('edit_expense_date').value = expense.expense_date.split(' ')[0]; // Handle datetime string
            document.getElementById('edit_category').value = expense.category;
            
            modal.style.display = 'flex';
        }
        
        function closeEditModal() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Initialize DataTable
        $(document).ready(function() {
            var table = $('#expensesTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "order": [[ 0, "desc" ]],
                "footerCallback": function ( row, data, start, end, display ) {
                    var api = this.api();
                    
                    var intVal = function ( i ) {
                        if (typeof i === 'string') {
                            return i.replace(/[^\d]/g, '') * 1;
                        }
                        return typeof i === 'number' ? i : 0;
                    };
                    
                    var total = api
                        .column( 3, { page: 'current'} )
                        .data()
                        .reduce( function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0 );
                        
                    var formattedTotal = new Intl.NumberFormat('en-US').format(total);
                    $('#tableTotalAmount').html('-' + formattedTotal + ' MMK');
                }
            });
        });
    </script>
</body>
</html>
