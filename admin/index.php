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
        
        .top-bar-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        /* Alert Messages */
        .success, .error {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .stat-icon.purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }
        
        .stat-content h3 {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .stat-content .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8fafc;
        }
        
        th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            color: #475569;
        }
        
        tbody tr {
            transition: background 0.2s ease;
        }
        
        tbody tr:hover {
            background: #f8fafc;
        }
        
        .category-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .badge-flavor {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-size {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-topping {
            background: #fce7f3;
            color: #9f1239;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 0.875rem;
            font-size: 0.8125rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-view {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .btn-view:hover {
            background: #c7d2fe;
        }
        
        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .btn-edit:hover {
            background: #bfdbfe;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-delete:hover {
            background: #fecaca;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 16px;
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .modal-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .close {
            font-size: 2rem;
            font-weight: 300;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .close:hover {
            color: #1e293b;
        }
        
        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            max-height: calc(90vh - 140px);
        }
        
        .product-details-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        
        .product-image img {
            width: 100%;
            border-radius: 12px;
            object-fit: cover;
        }
        
        .detail-row {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row strong {
            color: #64748b;
            font-weight: 600;
        }
        
        .detail-row span {
            color: #1e293b;
        }
        
        /* Edit Modal Form Styles */
        .form-group-modal {
            margin-bottom: 1.25rem;
        }
        
        .form-group-modal label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.875rem;
        }
        
        .form-group-modal input,
        .form-group-modal textarea,
        .form-group-modal select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }
        
        .form-group-modal input:focus,
        .form-group-modal textarea:focus,
        .form-group-modal select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group-modal textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row-modal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .btn-cancel {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        
        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .product-details-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row-modal {
                grid-template-columns: 1fr;
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
                    <a href="index.php" class="nav-link active">
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
                <h1 class="page-title">Product Management</h1>
                <div class="top-bar-actions">
                    <button onclick="showAddModal()" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add New Product
                    </button>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-check-circle"></i>
                    <?php
                        switch($_GET['success']) {
                            case 'product_updated':
                                echo 'Product updated successfully!';
                                break;
                            case 'product_added':
                                echo 'Product added successfully!';
                                break;
                            default:
                                echo 'Operation completed successfully!';
                        }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php
                        switch($_GET['error']) {
                            case 'name_required':
                                echo 'Product name is required';
                                break;
                            case 'invalid_price':
                                echo 'Price must be greater than 0';
                                break;
                            case 'invalid_quantity':
                                echo 'Quantity cannot be negative';
                                break;
                            case 'invalid_category':
                                echo 'Invalid category selected';
                                break;
                            case 'invalid_discount':
                                echo 'Discount percentage must be between 0 and 100';
                                break;
                            case 'update_failed':
                                echo 'Failed to update product';
                                if (isset($_GET['message'])) {
                                    echo ': ' . htmlspecialchars($_GET['message']);
                                }
                                break;
                            case 'create_failed':
                                echo 'Failed to create product';
                                if (isset($_GET['message'])) {
                                    echo ': ' . htmlspecialchars($_GET['message']);
                                }
                                break;
                            default:
                                echo 'An error occurred';
                        }
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Products</h3>
                        <div class="stat-value"><?= $stats['total_products'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Orders</h3>
                        <div class="stat-value"><?= $stats['total_orders'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Pending Orders</h3>
                        <div class="stat-value"><?= $stats['pending_orders'] ?></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Customers</h3>
                        <div class="stat-value"><?= $stats['total_customers'] ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Products Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Products</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                            <td><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</td>
                            <td><strong><?= number_format($product['price'], 0) ?> MMK</strong></td>
                            <td>
                                <span class="category-badge badge-<?= $product['category'] ?>">
                                    <?= ucfirst($product['category']) ?>
                                </span>
                            </td>
                            <td><?= $product['quantity'] ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-sm btn-view" onclick="showProductDetails('<?= $product['id'] ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-sm btn-edit" onclick="showEditModal('<?= $product['id'] ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="delete_product.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="btn-sm btn-delete" onclick="return confirm('Delete this product?')">
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
                            <span id="modalName"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Description:</strong>
                            <span id="modalDescription"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Price:</strong>
                            <span id="modalPrice"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Category:</strong>
                            <span id="modalCategory"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Stock Quantity:</strong>
                            <span id="modalQuantity"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Product ID:</strong>
                            <span id="modalId"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Created:</strong>
                            <span id="modalCreated"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Last Updated:</strong>
                            <span id="modalUpdated"></span>
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
                <h2>Edit Product</h2>
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
                    
                    <div class="form-group-modal">
                        <label for="editDiscount">Discount Percentage (%)</label>
                        <input type="number" id="editDiscount" name="discount_percentage" min="0" max="100" step="0.01" value="0">
                    </div>
                    
                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label for="editDiscountStart">Discount Start</label>
                            <input type="datetime-local" id="editDiscountStart" name="discount_start_date">
                        </div>
                        
                        <div class="form-group-modal">
                            <label for="editDiscountEnd">Discount End</label>
                            <input type="datetime-local" id="editDiscountEnd" name="discount_end_date">
                        </div>
                    </div>
                    
                    <div class="form-group-modal">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="editFeatured" name="is_featured">
                            <span>Mark as Featured Product</span>
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeEditModal()" style="flex: 1;">
                            Cancel
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
                <h2>Add New Product</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addForm" method="POST" action="create_product.php">
                    <div class="form-group-modal">
                        <label for="addName">Product Name *</label>
                        <input type="text" id="addName" name="name" required>
                    </div>
                    
                    <div class="form-group-modal">
                        <label for="addDescription">Description</label>
                        <textarea id="addDescription" name="description" rows="3"></textarea>
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
                    
                    <div class="form-group-modal">
                        <label for="addDiscount">Discount Percentage (%)</label>
                        <input type="number" id="addDiscount" name="discount_percentage" min="0" max="100" step="0.01" value="0" placeholder="0">
                        <small style="color: #94a3b8; font-size: 0.8125rem; margin-top: 4px; display: block;">Enter discount percentage (0-100). Leave 0 for no discount.</small>
                    </div>
                    
                    <div class="form-row-modal">
                        <div class="form-group-modal">
                            <label for="addDiscountStart">Discount Start</label>
                            <input type="datetime-local" id="addDiscountStart" name="discount_start_date">
                        </div>
                        
                        <div class="form-group-modal">
                            <label for="addDiscountEnd">Discount End</label>
                            <input type="datetime-local" id="addDiscountEnd" name="discount_end_date">
                        </div>
                    </div>
                    
                    <div class="form-group-modal">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="addFeatured" name="is_featured">
                            <span>Mark as Featured Product</span>
                        </label>
                        <small style="color: #94a3b8; font-size: 0.8125rem; margin-top: 4px; display: block;">Featured products appear prominently on the homepage</small>
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

    <script>
        const products = <?= json_encode($products) ?>;
        
        function showProductDetails(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;
            
            document.getElementById('modalTitle').textContent = product.name + ' - Details';
            document.getElementById('modalName').textContent = product.name;
            document.getElementById('modalDescription').textContent = product.description || 'No description';
            document.getElementById('modalPrice').textContent = new Intl.NumberFormat().format(product.price) + ' MMK';
            document.getElementById('modalCategory').innerHTML = `<span class="category-badge badge-${product.category}">${product.category.charAt(0).toUpperCase() + product.category.slice(1)}</span>`;
            document.getElementById('modalQuantity').textContent = product.quantity;
            document.getElementById('modalId').textContent = product.id;
            document.getElementById('modalCreated').textContent = new Date(product.created_at).toLocaleString();
            document.getElementById('modalUpdated').textContent = new Date(product.updated_at).toLocaleString();
            
            let imageUrl;
            if (product.image_url) {
                imageUrl = product.image_url.startsWith('images/') 
                    ? '../' + product.image_url 
                    : product.image_url;
            } else {
                imageUrl = 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop';
            }
            
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalImage').alt = product.name;
            
            document.getElementById('modalImage').onerror = function() {
                this.src = 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop';
            };
            
            document.getElementById('productModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
            const editModal = document.getElementById('editModal');
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
            const addModal = document.getElementById('addModal');
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
        }
        
        function showAddModal() {
            // Reset form
            document.getElementById('addForm').reset();
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function showEditModal(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;
            
            // Populate form fields
            document.getElementById('editProductId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editDescription').value = product.description || '';
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editCategory').value = product.category;
            document.getElementById('editQuantity').value = product.quantity;
            document.getElementById('editImageUrl').value = product.image_url || '';
            document.getElementById('editDiscount').value = product.discount_percentage || 0;
            
            // Handle datetime fields
            if (product.discount_start_date) {
                const startDate = new Date(product.discount_start_date);
                document.getElementById('editDiscountStart').value = formatDateTimeLocal(startDate);
            } else {
                document.getElementById('editDiscountStart').value = '';
            }
            
            if (product.discount_end_date) {
                const endDate = new Date(product.discount_end_date);
                document.getElementById('editDiscountEnd').value = formatDateTimeLocal(endDate);
            } else {
                document.getElementById('editDiscountEnd').value = '';
            }
            
            document.getElementById('editFeatured').checked = product.is_featured == 1;
            
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function formatDateTimeLocal(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
    </script>
</body>
</html>
