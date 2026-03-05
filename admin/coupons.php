<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$success = '';
$error = '';

try {
    $db = Database::getInstance()->getConnection();

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $code = strtoupper(trim($_POST['code']));
            $type = $_POST['type']; // percentage or fixed
            $value = (float)$_POST['value'];
            $min_order = (float)($_POST['min_order'] ?? 0);
            $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
            $max_uses_per_user = (int)($_POST['max_uses_per_user'] ?? 1);
            $valid_from = $_POST['valid_from'];
            $valid_until = $_POST['valid_until'];

            // Server-side validation
            if (empty($code)) {
                $error = "Coupon code is required.";
            } else {
                // Check if code already exists
                $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
                $stmt->execute([$code]);
                if ($stmt->fetch()) {
                    $error = "Coupon code '$code' already exists.";
                } else {
                    $stmt = $db->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_uses, max_uses_per_user, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $type, $value, $min_order, $max_uses, $max_uses_per_user, $valid_from, $valid_until]);
                    $success = "Coupon code '$code' created successfully.";
                }
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'toggle') {
            $id = (int)$_POST['id'];
            $status = (int)$_POST['status'];
            $stmt = $db->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = "Coupon status updated.";
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Coupon deleted successfully.";
        }
    }

    // Get all coupons with usage counts
    $coupons = $db->query("
        SELECT c.*, COUNT(cu.id) as used_count 
        FROM coupons c 
        LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id 
        GROUP BY c.id 
        ORDER BY c.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "An error occurred: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupons - Scoops Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        h1, h2, h3 { font-family: 'Playfair Display', serif; }

        /* Sidebar Copy from dashboard */
        .sidebar {
            width: 280px;
            background: var(--surface);
            color: var(--text-main);
            padding: 2.5rem 0;
            position: fixed;
            height: 100vh;
            border-right: 1px solid rgba(44, 41, 109, 0.08);
            z-index: 100;
        }

        .sidebar-header { padding: 0 2.5rem 2.5rem; text-align: center; }
        .sidebar-logo { text-decoration: none; color: var(--primary); font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sidebar-logo i { background: linear-gradient(135deg, var(--primary), var(--primary-light)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 2rem; }

        .sidebar-nav { padding: 0; }
        .nav-section { margin-bottom: 2rem; }
        .nav-section-title { padding: 0 2.5rem; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1rem; letter-spacing: 1.5px; opacity: 0.7; }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 1rem 2rem;
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

        /* Main Content */
        .main-content { flex: 1; margin-left: 280px; padding: 2.5rem; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
        
        .header-title h1 { font-size: 2.2rem; color: var(--text-main); margin-bottom: 0.5rem; }
        .header-title p { color: var(--text-muted); font-size: 1rem; font-weight: 500; }

        .panel { background: var(--surface); border-radius: 24px; padding: 2rem; box-shadow: var(--card-shadow); margin-bottom: 2.5rem; border: 1px solid rgba(255, 255, 255, 0.8); }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem; border-bottom: 2px solid rgba(44, 41, 109, 0.05); padding-bottom: 1rem; }
        .panel-title { font-size: 1.4rem; color: var(--text-main); font-weight: 800; }

        /* Form Styling */
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.6rem; letter-spacing: 0.5px; }
        .form-control { width: 100%; border: 2px solid #eef2f7; background: #f8fafc; padding: 12px 16px; border-radius: 12px; font-weight: 600; font-family: 'Plus Jakarta Sans', sans-serif; transition: var(--transition); }
        .form-control:focus { border-color: var(--primary); background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(108, 93, 252, 0.1); }
        
        .code-input-group { display: flex; gap: 10px; }
        .btn-generate { background: var(--secondary); color: white; padding: 0 1.2rem; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; transition: var(--transition); }
        .btn-generate:hover { background: #334155; transform: translateY(-2px); }

        .btn-submit { background: var(--primary); color: white; font-weight: 800; padding: 14px 28px; border-radius: 14px; border: none; width: 100%; margin-top: 1rem; cursor: pointer; transition: var(--transition); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 15px rgba(108, 93, 252, 0.2); }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(108, 93, 252, 0.3); }

        /* Table Styling */
        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .premium-table th { padding: 1.2rem 1.5rem; font-weight: 700; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid #f1f5f9; text-align: left; }
        .premium-table td { padding: 1.2rem 1.5rem; border-bottom: 1px solid #f1f5f9; font-weight: 600; }
        .premium-table tr:last-child td { border-bottom: none; }
        .coupon-code-badge { 
            background: rgba(108, 93, 252, 0.08); 
            color: var(--primary); 
            font-weight: 800; 
            font-family: monospace; 
            font-size: 1.1rem; 
            padding: 6px 12px; 
            border-radius: 8px; 
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
        }
        .coupon-code-badge:hover {
            background: rgba(108, 93, 252, 0.15);
            border-color: var(--primary);
            transform: scale(1.05);
        }
        .copy-hint { font-size: 0.7rem; opacity: 0.5; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-inactive { background: #fef2f2; color: #991b1b; }

        .actions { display: flex; gap: 8px; }
        .btn-action { width: 34px; height: 34px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; font-size: 0.9rem; }
        .btn-toggle-on { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .btn-toggle-off { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .btn-action:hover { transform: scale(1.1); }
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
                <a href="accounting.php" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Accounting</span>
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                    <!-- badge count could go here -->
                </a>
                <a href="coupons.php" class="nav-link active">
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

    <main class="main-content">
        <div class="top-bar">
            <div class="header-title">
                <h1>Coupon Generator</h1>
                <p>Create and manage discount codes for your customers</p>
            </div>
        </div>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Create New Coupon</h2>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Coupon Code</label>
                        <div class="code-input-group">
                            <input type="text" name="code" id="coupon_code" class="form-control" placeholder="E.g. SUMMER24" required>
                            <button type="button" class="btn-generate" onclick="generateCode()" title="Generate Random Code">
                                <i class="fas fa-random"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount Type</label>
                        <select name="type" id="discount_type" class="form-control" onchange="updatePlaceholder()">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (MMK)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Discount Value</label>
                        <input type="number" name="value" id="discount_value" step="0.01" class="form-control" placeholder="E.g. 15 for 15%" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min. Order Amount (MMK)</label>
                        <input type="number" name="min_order" class="form-control" value="0" placeholder="E.g. 5000">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Usage Limit (Total)</label>
                        <input type="number" name="max_uses" class="form-control" placeholder="Unlimited if empty">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Uses per Customer</label>
                        <input type="number" name="max_uses_per_user" class="form-control" value="1" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Valid From</label>
                        <input type="datetime-local" name="valid_from" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input type="datetime-local" name="valid_until" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+1 month')) ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus-circle"></i> Generate Coupon Code
                </button>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Active & Past Coupons</h2>
            </div>
            <div style="overflow-x: auto;">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Min. Order</th>
                            <th>Usage</th>
                            <th>Expiry</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">No coupons found</td></tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $c): ?>
                            <tr>
                                <td>
                                    <span class="coupon-code-badge" onclick="copyToClipboard(this, '<?= htmlspecialchars($c['code']) ?>')" title="Click to Copy">
                                        <?= htmlspecialchars($c['code']) ?>
                                        <i class="far fa-copy copy-hint"></i>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= number_format($c['discount_value']) ?><?= $c['discount_type'] === 'percentage' ? '%' : ' MMK' ?></strong>
                                </td>
                                <td><?= number_format($c['min_order_amount']) ?> MMK</td>
                                <td>
                                    <?= $c['used_count'] ?> / <?= $c['max_uses'] ?? '∞' ?>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">(Limit <?= $c['max_uses_per_user'] ?>/user)</div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;">
                                        Until <?= date('M d, Y', strtotime($c['valid_until'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $c['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $c['is_active'] ? 'Active' : 'Paused' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $c['is_active'] ? 0 : 1 ?>">
                                        <button type="submit" class="btn-action <?= $c['is_active'] ? 'btn-toggle-off' : 'btn-toggle-on' ?>" title="<?= $c['is_active'] ? 'Pause' : 'Activate' ?>">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this coupon?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        function generateCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('coupon_code').value = 'SCOOP-' + code;
        }

        function updatePlaceholder() {
            const type = document.getElementById('discount_type').value;
            const input = document.getElementById('discount_value');
            if (type === 'percentage') {
                input.placeholder = "E.g. 15 for 15%";
            } else {
                input.placeholder = "E.g. 5000 for 5000 MMK";
            }
        }

        // Initialize placeholder on load
        document.addEventListener('DOMContentLoaded', updatePlaceholder);

        function copyToClipboard(element, text) {
            navigator.clipboard.writeText(text).then(() => {
                const originalContent = element.innerHTML;
                element.innerHTML = 'COPIED! <i class="fas fa-check"></i>';
                element.style.background = '#dcfce7';
                element.style.color = '#15803d';
                
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Code copied: ' + text,
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                });

                setTimeout(() => {
                    element.innerHTML = originalContent;
                    element.style.background = '';
                    element.style.color = '';
                }, 2000);
            });
        }

        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?= $success ?>',
                confirmButtonColor: '#6c5dfc'
            });
        <?php endif; ?>

        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= $error ?>',
                confirmButtonColor: '#ef4444'
            });
        <?php endif; ?>
    </script>
</body>
</html>
