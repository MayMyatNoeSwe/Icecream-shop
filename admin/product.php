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
    
    // Get products
    $stmt = $db->query("SELECT * FROM products ORDER BY category, name");
    $products = $stmt->fetchAll();
    
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
    <title>Admin Dashboard - Product Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
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
        .nav-section-title { padding: 0 2.5rem; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1rem; letter-spacing: 1.5px; opacity: 0.7; }
        
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

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-primary { background: var(--primary); color: white; box-shadow: 0 8px 20px rgba(108, 93, 252, 0.2); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(108, 93, 252, 0.3); background: #5a4eea; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.25rem 1.5rem;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: var(--transition);
        }

        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(108, 93, 252, 0.1); }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0; width: 100px; height: 100px;
            background: linear-gradient(135deg, rgba(108, 93, 252, 0.05) 0%, rgba(255,255,255,0) 100%);
            border-radius: 0 0 0 100%;
        }

        .stat-icon {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .bg-purple { background: rgba(108, 93, 252, 0.1); color: var(--primary); }
        .bg-green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .bg-orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .bg-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

        .stat-info .stat-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .stat-info .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--text-main); }

        /* Panel & Table Styling */
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
            margin-bottom: 1.25rem;
        }

        .panel-title { font-size: 1.2rem; color: var(--text-main); font-weight: 800; }

        .table-responsive { width: 100%; overflow-x: auto; }

        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .premium-table thead th {
            text-align: left;
            padding: 0.8rem 0.75rem;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom: 2px solid rgba(44, 41, 109, 0.05);
        }

        .premium-table tbody tr { transition: var(--transition); }
        .premium-table tbody tr:hover { background: rgba(108, 93, 252, 0.03); }

        .premium-table tbody td {
            padding: 0.8rem 0.75rem;
            border-bottom: 1px solid rgba(44, 41, 109, 0.05);
            font-weight: 600;
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .product-cell { display: flex; align-items: center; gap: 10px; }
        .product-img-tiny { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .product-name-info { display: flex; flex-direction: column; }
        .product-name-info strong { color: var(--text-main); display: block; }
        .product-name-info small { color: var(--text-muted); font-size: 0.75rem; }

        .status-badge {
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .category-badge { padding: 4px 12px; border-radius: 10px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .badge-flavor { background: rgba(108, 93, 252, 0.1); color: var(--primary); }
        .badge-size { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .badge-topping { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        /* Action Buttons */
        .btn-action {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn-view { background: rgba(108, 93, 252, 0.08); color: var(--primary); }
        .btn-edit { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .btn-delete { background: rgba(239, 68, 68, 0.08); color: var(--danger); }

        .btn-action:hover { transform: scale(1.1); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        /* DataTables Customization */
        .dataTables_wrapper .dataTables_length select {
            padding: 8px 12px; border-radius: 10px; border: 2px solid #eef2f7; font-weight: 700;
        }
        .dataTables_wrapper .dataTables_filter input {
            padding: 8px 15px; border-radius: 10px; border: 2px solid #eef2f7; font-weight: 600; margin-left: 10px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 10px !important; border: none !important; font-weight: 700 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary) !important; color: white !important;
        }

        /* Modal Enhancements */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(44, 41, 109, 0.4); backdrop-filter: blur(8px);
            overflow-y: auto; padding: 20px 0;
            align-items: center; justify-content: center;
        }
        .modal-content {
            background: white; margin: auto; padding: 0; border-radius: 24px;
            width: 90%; max-width: 800px; box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            border: 1px solid rgba(255, 255, 255, 0.8); overflow: hidden;
            animation: modalSlideUp 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            position: relative;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        @keyframes modalSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .modal-header { padding: 1.25rem 2rem; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .modal-header h2 { font-size: 1.6rem; color: var(--text-main); font-family: 'Playfair Display', serif; position: relative; }
        .modal-header h2::after { content: ''; position: absolute; bottom: -8px; left: 0; width: 40px; height: 3px; background: linear-gradient(90deg, var(--primary), transparent); }
        .close { font-size: 1.5rem; font-weight: 700; color: var(--text-muted); cursor: pointer; transition: 0.2s; padding: 5px; }
        .close:hover { color: var(--danger); transform: rotate(90deg); }

        .modal-body { padding: 2rem; overflow-y: auto; flex-grow: 1; }
        .form-group-modal { margin-bottom: 1.25rem; }
        .form-group-modal label { display: block; font-weight: 800; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px; }
        .form-group-modal input, .form-group-modal textarea, .form-group-modal select {
            width: 100%; padding: 12px 16px; border-radius: 12px; border: 2px solid #eef2f7; font-family: inherit; font-weight: 600; background: #f8fafc; transition: var(--transition);
        }
        .form-group-modal input:focus { border-color: var(--primary); outline: none; background: white; }
        .form-row-modal { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }

        .btn-cancel { background: #f1f5f9; color: var(--text-muted); border: none; font-weight: 700; padding: 12px 24px; border-radius: 14px; cursor: pointer; transition: var(--transition); }
        .btn-cancel:hover { background: #e2e8f0; color: var(--text-main); }

        /* Product Details Modal Styling */
        .product-details-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 2.5rem; align-items: start; }
        .product-image img { width: 100%; height: 400px; border-radius: 20px; object-fit: cover; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .product-info { display: flex; flex-direction: column; gap: 1.5rem; }
        .detail-row { display: flex; flex-direction: column; gap: 6px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-row strong { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); font-weight: 800; }
        .detail-row span { font-size: 1.05rem; color: var(--text-main); font-weight: 700; }

        /* Premium Search Styling */
        .dataTables_filter input.premium-search {
            background: #f8fafc;
            border: 2px solid #eef2f7;
            padding: 10px 20px !important;
            border-radius: 12px !important;
            width: 250px !important;
            transition: var(--transition);
        }
        .dataTables_filter input.premium-search:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 8px 15px rgba(108, 93, 252, 0.08);
            outline: none;
        }

        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; max-width: 100%; padding: 1.5rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .form-row-modal { grid-template-columns: 1fr; }
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
                    <a href="orders.php" class="nav-link">
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
                    <a href="product.php" class="nav-link active">
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
                    <h1>Product Excellence</h1>
                    <p>Curate your premium flavors and toppings</p>
                </div>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="panel" style="background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2); margin-bottom: 2rem; padding: 1rem 2rem;">
                    <strong style="color: var(--success);">Success!</strong> 
                    <span style="color: #065f46; font-size: 0.95rem;">
                        <?php 
                            switch($_GET['success']) {
                                case 'product_added': echo 'New product has been added to your collection.'; break;
                                case 'product_updated': echo 'Product details have been successfully refined.'; break;
                                case 'product_deleted': echo 'The product has been removed from the catalog.'; break;
                                default: echo 'Operation completed successfully.';
                            }
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="panel" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); margin-bottom: 2rem; padding: 1rem 2rem;">
                    <strong style="color: var(--danger);">Error!</strong> 
                    <span style="color: #991b1b; font-size: 0.95rem;">
                        <?php 
                            switch($_GET['error']) {
                                case 'name_required': echo 'Product name is required'; break;
                                case 'invalid_price': echo 'Price must be greater than 0'; break;
                                default: echo 'An unexpected error occurred. Please try again.';
                            }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <!-- Stats Overview -->
<?php
// Calculate total stock and featured count if not already in $stats
$total_stock = 0;
$featured_count = 0;
foreach ($products as $p) {
    if (isset($p['quantity'])) $total_stock += $p['quantity'];
    if (isset($p['is_featured']) && $p['is_featured'] == 1) $featured_count++;
}
?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fas fa-ice-cream"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total Items</span>
                        <div class="stat-value"><?= $stats['total_products'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Orders Ref</span>
                        <div class="stat-value"><?= $stats['total_orders'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Stock Level</span>
                        <div class="stat-value"><?= number_format($total_stock) ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Featured</span>
                        <div class="stat-value"><?= $featured_count ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Product Table Panel -->
            <div class="panel">
                <div class="panel-header" style="flex-direction: column; align-items: flex-start; gap: 15px;">
                    <h2 class="panel-title">Product Catalog</h2>
                    <div id="filterContainer" style="display: flex; gap: 8px; width: 100%; flex-wrap: wrap;">
                        <input type="text" class="custom-filter" data-col="0" placeholder="Filter Name" style="flex: 1; min-width: 150px; padding: 6px 12px; border: 1px solid #eef2f7; border-radius: 8px; font-size: 0.8rem; font-weight: 500;">
                        <input type="text" class="custom-filter" data-col="1" placeholder="Filter Description" style="flex: 1; min-width: 150px; padding: 6px 12px; border: 1px solid #eef2f7; border-radius: 8px; font-size: 0.8rem; font-weight: 500;">
                        <input type="text" class="custom-filter" data-col="2" placeholder="Filter Price" style="flex: 1; min-width: 150px; padding: 6px 12px; border: 1px solid #eef2f7; border-radius: 8px; font-size: 0.8rem; font-weight: 500;">
                        <input type="text" class="custom-filter" data-col="3" placeholder="Filter Category" style="flex: 1; min-width: 150px; padding: 6px 12px; border: 1px solid #eef2f7; border-radius: 8px; font-size: 0.8rem; font-weight: 500;">
                        <input type="text" class="custom-filter" data-col="4" placeholder="Filter Stock" style="flex: 1; min-width: 150px; padding: 6px 12px; border: 1px solid #eef2f7; border-radius: 8px; font-size: 0.8rem; font-weight: 500;">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="productTable" class="premium-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <?php 
                                            if ($product['image_url']) {
                                                $img = $product['image_url'];
                                                if (strpos($img, 'images/') === 0) {
                                                    $imgPath = '../' . $img;
                                                } else {
                                                    $imgPath = $img;
                                                }
                                            } else {
                                                $imgPath = 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=100&h=100&fit=crop';
                                            }
                                        ?>
                                        <img src="<?= $imgPath ?>" class="product-img-tiny" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=100&h=100&fit=crop'">
                                        <div class="product-name-info">
                                            <strong><?= htmlspecialchars($product['name']) ?></strong>
                                            <small>ID: <?= substr($product['id'] ?? '', 0, 8) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">
                                        <?= mb_strimwidth(htmlspecialchars($product['description'] ?? ''), 0, 40, '...') ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--text-main);"><?= number_format($product['price']) ?></strong>
                                    <small style="color: var(--text-muted); font-size: 0.7rem; font-weight: 800;"> MMK</small>
                                </td>
                                <td>
                                    <span class="category-badge badge-<?= $product['category'] ?>">
                                        <?= $product['category'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-weight: 800; color: <?= $product['quantity'] < 20 ? 'var(--danger)' : 'var(--text-main)' ?>;">
                                            <?= $product['quantity'] ?>
                                        </span>
                                        <?php if ($product['quantity'] < 20): ?>
                                            <i class="fas fa-exclamation-triangle" style="color: var(--warning); font-size: 0.8rem;" title="Low Stock"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;" class="action-container">
                                        <button type="button" class="btn-action btn-view" data-id="<?= $product['id'] ?>" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn-action btn-edit" data-id="<?= $product['id'] ?>" title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="delete_product.php" onsubmit="return confirm('Archive this premium product? This action cannot be revoked.')" style="display: inline;">
                                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="btn-action btn-delete" title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Product Details Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Product Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="product-details-grid">
                    <div class="product-image">
                        <img id="modalImage" src="" alt="Product Image">
                    </div>
                    <div class="product-info">
                        <div class="detail-row">
                            <strong>Name:</strong>
                            <span id="modalName" style="font-weight: 800; color: var(--text-main);"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Description:</strong>
                            <span id="modalDescription" style="color: var(--text-muted); line-height: 1.6;"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Price:</strong>
                            <span id="modalPrice" style="color: var(--primary); font-weight: 800; font-size: 1.1rem;"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Category:</strong>
                            <span id="modalCategory"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Stock Quantity:</strong>
                            <span id="modalQuantity" style="font-weight: 700;"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Product ID:</strong>
                            <span id="modalId"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Refine Product</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST" action="update_product.php">
                    <input type="hidden" id="editProductId" name="id">
                    
                    <div class="form-group-modal">
                        <label for="editName">Product Name *</label>
                        <input type="text" id="editName" name="name" required>
                    </div>
                    
                    <div class="form-group-modal">
                        <label for="editDescription">Description</label>
                        <textarea id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label for="editPrice">Price (MMK) *</label>
                            <input type="number" id="editPrice" name="price" step="1" min="0" required>
                        </div>
                        
                        <div class="form-group-modal">
                            <label for="editCategory">Category *</label>
                            <select id="editCategory" name="category" required>
                                <option value="flavor">Flavor</option>
                                <option value="size">Size</option>
                                <option value="topping">Topping</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group-modal">
                        <label for="editQuantity">Stock Quantity *</label>
                        <input type="number" id="editQuantity" name="quantity" min="0" required>
                    </div>
                    
                    <div class="form-group-modal">
                        <label for="editImageUrl">Image URL</label>
                        <input type="text" id="editImageUrl" name="image_url" placeholder="images/filename.jpg">
                    </div>

                    <!-- Promotions & Tags -->
                    <div style="background: #f8fafc; padding: 1.25rem; border-radius: 16px; margin-bottom: 1.5rem; border: 1px solid #eef2f7;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 1rem; font-weight: 800;">Promotions & Visibility</h4>
                        
                        <div class="form-row-modal">
                            <div class="form-group-modal">
                                <label for="editDiscount">Discount (%)</label>
                                <input type="number" id="editDiscount" name="discount_percentage" step="0.01" min="0" max="100">
                            </div>
                            <div class="form-group-modal" style="display: flex; align-items: center; gap: 10px; margin-top: 1.5rem;">
                                <input type="checkbox" id="editFeatured" name="is_featured" style="width: auto;">
                                <label for="editFeatured" style="margin-bottom: 0;">Featured Star</label>
                            </div>
                        </div>

                        <div class="form-row-modal">
                            <div class="form-group-modal">
                                <label for="editDiscountStart">Start Date</label>
                                <input type="datetime-local" id="editDiscountStart" name="discount_start_date">
                            </div>
                            <div class="form-group-modal">
                                <label for="editDiscountEnd">End Date</label>
                                <input type="datetime-local" id="editDiscountEnd" name="discount_end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeEditModal()" style="flex: 1;">
                            Discard
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2>Create New Excellence</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addForm" method="POST" action="create_product.php">
                    <div class="form-group-modal">
                        <label for="addName">Product Name *</label>
                        <input type="text" id="addName" name="name" required placeholder="e.g. Vanilla Bean Bliss">
                    </div>
                    
                    <div class="form-group-modal">
                        <label for="addDescription">Description</label>
                        <textarea id="addDescription" name="description" rows="3" placeholder="Craft a compelling story for this product..."></textarea>
                    </div>
                    
                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label for="addPrice">Price (MMK) *</label>
                            <input type="number" id="addPrice" name="price" step="1" min="0" required>
                        </div>
                        
                        <div class="form-group-modal">
                            <label for="addCategory">Category *</label>
                            <select id="addCategory" name="category" required>
                                <option value="">Select Category</option>
                                <option value="flavor">Flavor</option>
                                <option value="size">Size</option>
                                <option value="topping">Topping</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group-modal">
                        <label for="addQuantity">Initial Stock Quantity *</label>
                        <input type="number" id="addQuantity" name="quantity" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group-modal">
                        <label for="addImageUrl">Image URL</label>
                        <input type="text" id="addImageUrl" name="image_url" placeholder="images/filename.jpg">
                        <small style="color: #94a3b8; font-size: 0.8125rem; margin-top: 4px; display: block;">Optional: Enter a URL for the product image</small>
                    </div>

                    <div style="background: #f8fafc; padding: 1.25rem; border-radius: 16px; margin-bottom: 1.5rem; border: 1px solid #eef2f7;">
                        <h4 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 1rem; font-weight: 800;">Promotions & Visibility</h4>
                        
                        <div class="form-row-modal">
                            <div class="form-group-modal">
                                <label for="addDiscount">Initial Discount (%)</label>
                                <input type="number" id="addDiscount" name="discount_percentage" step="0.01" min="0" max="100" value="0">
                            </div>
                            <div class="form-group-modal" style="display: flex; align-items: center; gap: 10px; margin-top: 1.5rem;">
                                <input type="checkbox" id="addFeatured" name="is_featured" style="width: auto;">
                                <label for="addFeatured" style="margin-bottom: 0;">Featured Star</label>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeAddModal()" style="flex: 1;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // DataTable
            var table = $('#productTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search catalog...",
                    "paginate": {
                        "previous": "<i class='fas fa-chevron-left'></i>",
                        "next": "<i class='fas fa-chevron-right'></i>"
                    }
                },
                "order": [[0, 'asc']]
            });

            // Apply custom filters
            $('.custom-filter').on('keyup change clear', function () {
                var colIndex = $(this).data('col');
                table.column(colIndex).search(this.value).draw();
            });
            
            // Premium Search Look
            $('.dataTables_filter input').addClass('premium-search');
        });

        const products = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]' ?>;
        console.log('Premium Catalog loaded with', Array.isArray(products) ? products.length : 'object-based', 'items');
        
        // Use event delegation for buttons to work through DataTables pagination/sorting
        $(document).on('click', '.btn-view', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).attr('data-id'); // Use attr to get exact string
            showProductDetails(id);
        });

        $(document).on('click', '.btn-edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).attr('data-id'); // Use attr to get exact string
            showEditModal(id);
        });

        function showProductDetails(productId) {
            console.log('Viewing details for:', productId);
            const productArr = Array.isArray(products) ? products : Object.values(products);
            const product = productArr.find(p => p.id == productId);
            if (!product) {
                console.error('Details not found for:', productId);
                return;
            }
            
            document.getElementById('modalTitle').textContent = product.name;
            document.getElementById('modalName').textContent = product.name;
            document.getElementById('modalDescription').textContent = product.description || 'No description provided for this premium item.';
            document.getElementById('modalPrice').textContent = new Intl.NumberFormat().format(product.price) + ' MMK';
            document.getElementById('modalCategory').innerHTML = `<span class="category-badge badge-${product.category}">${product.category}</span>`;
            document.getElementById('modalQuantity').textContent = product.quantity;
            document.getElementById('modalId').textContent = product.id;
            
            let imageUrl;
            if (product.image_url) {
                imageUrl = product.image_url.startsWith('images/') 
                    ? '../' + product.image_url 
                    : product.image_url;
            } else {
                imageUrl = 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop';
            }
            
            const modalImg = document.getElementById('modalImage');
            modalImg.src = imageUrl;
            modalImg.onerror = function() {
                this.src = 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop';
            };
            
            document.getElementById('productModal').style.display = 'flex';
        }
        
        function closeModal() { document.getElementById('productModal').style.display = 'none'; }
        function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }
        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
        
        function showAddModal() {
            document.getElementById('addForm').reset();
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function showEditModal(productId) {
            console.log('Requesting edit for:', productId);
            try {
                const productArr = Array.isArray(products) ? products : Object.values(products);
                const product = productArr.find(p => p.id == productId);
                if (!product) {
                    console.error('Record not found in local catalog:', productId);
                    return;
                }
                
                document.getElementById('editProductId').value = product.id;
                document.getElementById('editName').value = product.name;
                document.getElementById('editDescription').value = product.description || '';
                document.getElementById('editPrice').value = product.price;
                document.getElementById('editCategory').value = product.category;
                document.getElementById('editQuantity').value = product.quantity;
                document.getElementById('editImageUrl').value = product.image_url || '';
                document.getElementById('editDiscount').value = product.discount_percentage || 0;
                document.getElementById('editFeatured').checked = product.is_featured == 1;

                const startInput = document.getElementById('editDiscountStart');
                const endInput = document.getElementById('editDiscountEnd');

                if (startInput) startInput.value = formatDateTime(product.discount_start_date);
                if (endInput) endInput.value = formatDateTime(product.discount_end_date);
                
                document.getElementById('editModal').style.display = 'flex';
            } catch (err) {
                console.error('Error opening edit modal:', err);
            }
        }

        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr || dateTimeStr === '0000-00-00 00:00:00') return '';
            try {
                // Ensure browser compatibility for MySQL date formats
                const normalized = dateTimeStr.replace(' ', 'T');
                const d = new Date(normalized);
                if (isNaN(d.getTime())) return '';
                
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                const hours = String(d.getHours()).padStart(2, '0');
                const minutes = String(d.getMinutes()).padStart(2, '0');
                
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            } catch (e) {
                console.error('Date parsing error:', e);
                return '';
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
