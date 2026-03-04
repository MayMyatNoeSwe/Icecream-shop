<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=orders.php');
    exit;
}

$orders = [];
$successMessage = '';
$errorMessage = '';
if (isset($_GET['reorder'])) {
    if ($_GET['reorder'] === 'success') {
        $successMessage = 'Items added to cart successfully!';
    } elseif ($_GET['reorder'] === 'failed_stock') {
        $errorMessage = 'Sorry, we couldn\'t reorder these items because they are currently out of stock.';
    } elseif ($_GET['reorder'] === 'error') {
        $errorMessage = 'An error occurred while processing your reorder. Please try again.';
    }
}

try {
    $db = Database::getInstance()->getConnection();
    date_default_timezone_set('Asia/Yangon');
    $userEmail = $_SESSION['user_email'];
    $stmt = $db->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email = ? ORDER BY o.order_date DESC");
    $stmt->execute([$userEmail]);
    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $stmt = $db->prepare("SELECT oi.*, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();

        // Automatic Status Update Logic
        $orderTime = strtotime($order['order_date']);
        $now = time();
        $minutesElapsed = ($now - $orderTime) / 60;

        if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed') {
            if ($minutesElapsed >= 15) {
                $order['status'] = 'completed';
            } elseif ($minutesElapsed >= 10) {
                $order['status'] = 'ready';
            } elseif ($minutesElapsed >= 5) {
                $order['status'] = 'preparing';
            } else {
                $order['status'] = 'pending';
            }
        }
        $order['minutes_elapsed'] = max(0, $minutesElapsed);
    }
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Scoops Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f1efe9;
            --primary-text: #2c296d;
            --secondary-text: #6b6b8d;
            --accent-color: #6c5dfc;
            --white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.6);
            --card-border: rgba(255, 255, 255, 0.4);
            --nav-bg: rgba(241, 239, 233, 0.8);
            --nav-height: 60px;
            --nav-scrolled-bg: rgba(255, 255, 255, 0.85);
            --transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            --hero-bg: #f1efe9;
        }

        [data-theme="dark"] {
            --bg-color: #1a1914;
            --primary-text: #f0f0f5;
            --secondary-text: #c4c4d9;
            --accent-color: #a78bfa;
            --white: #1e1e2f;
            --card-bg: rgba(30, 30, 47, 0.7);
            --card-border: rgba(167, 139, 250, 0.2);
            --nav-bg: rgba(26, 25, 20, 0.95);
            --nav-scrolled-bg: rgba(26, 25, 20, 0.92);
            --hero-bg: #1a1914;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--primary-text);
            min-height: 100vh;
            margin: 0;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }
        .playfair { font-family: 'Playfair Display', serif; }

        .orders-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: calc(var(--nav-height) + 40px) 24px 60px;
        }

        .header-content { text-align: center; margin-bottom: 50px; }
        .header-content h1 { font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 900; margin-bottom: 10px; }
        .header-content p { color: var(--secondary-text); font-weight: 600; font-size: 1.1rem; }

        .order-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 30px;
            border: 1px solid var(--card-border);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .order-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.05); }

        .order-meta { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 2px solid var(--card-border); padding-bottom: 20px; }
        .order-info h3 { font-size: 0.8rem; font-weight: 800; color: var(--secondary-text); margin-bottom: 5px; opacity: 0.7; letter-spacing: 1px; }
        .order-date { font-weight: 700; font-size: 1.1rem; }

        .status-badge {
            padding: 8px 18px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-pending { background: rgba(250, 204, 21, 0.1); color: #ca8a04; border: 1px solid rgba(250, 204, 21, 0.2); }
        .status-preparing { background: rgba(108, 93, 252, 0.1); color: var(--accent-color); border: 1px solid rgba(108, 93, 252, 0.2); }
        .status-ready { background: rgba(34, 197, 94, 0.1); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.2); }
        .status-completed { background: rgba(34, 197, 94, 0.15); color: #16a34a; border: 1px solid rgba(34, 197, 94, 0.2); }
        .status-cancelled { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); }

        .item-list { margin-bottom: 25px; }
        .item-row { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--card-border); align-items: center; }
        .item-row:last-child { border-bottom: none; }
        .item-img { width: 60px; height: 60px; border-radius: 16px; object-fit: cover; background: rgba(108, 93, 252, 0.05); border: 2px solid var(--card-border); transition: transform 0.3s ease; }
        .item-row:hover .item-img { transform: scale(1.05); }
        .item-name { font-weight: 800; font-size: 1.05rem; }
        .item-sub { font-size: 0.85rem; color: var(--secondary-text); margin-top: 4px; font-weight: 600; }
        .item-price { font-weight: 800; color: var(--accent-color); font-size: 1rem; }

        .order-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 25px; border-top: 2px solid var(--card-border); }
        .order-total { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 900; }
        .total-label { font-size: 0.8rem; font-weight: 800; color: var(--secondary-text); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }

        .actions-group { display: flex; gap: 12px; }
        .btn-premium { border: none; padding: 12px 28px; border-radius: 100px; font-weight: 800; font-size: 0.9rem; transition: var(--transition); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-details { background: rgba(108, 93, 252, 0.08); color: var(--accent-color); border: 1px solid rgba(108, 93, 252, 0.15); }
        .btn-details:hover { background: var(--accent-color); color: white; }
        .btn-reorder { background: var(--primary-text); color: white; box-shadow: 0 4px 15px rgba(44, 41, 109, 0.2); }
        .btn-reorder:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(44, 41, 109, 0.3); color: white; }

        .order-details-pane { display: none; background: rgba(0,0,0,0.03); border-radius: 20px; padding: 25px; margin-top: 25px; border: 1px solid var(--card-border); animation: slideDown 0.4s ease forwards; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .detail-item h4 { font-size: 0.65rem; font-weight: 800; color: var(--secondary-text); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; opacity: 0.6; }
        .detail-item p { font-weight: 700; color: var(--primary-text); margin: 0; font-size: 0.95rem; }

        .empty-orders { text-align: center; padding: 100px 0; display: flex; flex-direction: column; align-items: center; }
        .empty-icon { font-size: 5rem; color: var(--secondary-text); opacity: 0.2; margin-bottom: 25px; }
        
        .back-nav { position: fixed; top: 30px; left: 30px; text-decoration: none; color: var(--primary-text); font-weight: 800; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; z-index: 1000; background: var(--card-bg); backdrop-filter: blur(10px); padding: 10px 20px; border-radius: 50px; border: 1px solid var(--card-border); transition: var(--transition); }
        .back-nav:hover { transform: translateX(-5px); border-color: var(--accent-color); color: var(--accent-color); }

        .stepper-container { margin: 25px 0; background: rgba(255,255,255,0.4); padding: 20px; border-radius: 22px; border: 1px solid var(--card-border); }
        
        .card-collapsible { 
            display: none; 
            animation: slideDown 0.4s ease forwards;
        }
        
        .chevron-icon {
            transition: transform 0.3s ease;
        }
        .order-card.expanded .chevron-icon {
            transform: rotate(180deg);
        }

        /* Progress Bar Refinement to match reference */
        .progress { 
            height: 12px !important; 
            background: #f0f0f0 !important; 
            border-radius: 6px !important;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            background-color: #4a90e2 !important; /* Brighter blue from reference */
            background-image: linear-gradient(
                45deg, 
                rgba(255, 255, 255, .15) 25%, 
                transparent 25%, 
                transparent 50%, 
                rgba(255, 255, 255, .15) 50%, 
                rgba(255, 255, 255, .15) 75%, 
                transparent 75%, 
                transparent
            ) !important;
            background-size: 1rem 1rem !important;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1) !important;
            animation: progress-bar-stripes 1s linear infinite !important;
            border-radius: 6px !important;
        }

        @keyframes progress-bar-stripes {
            from { background-position: 1rem 0; }
            to { background-position: 0 0; }
        }

        .card-collapsible .progress-bar {
            width: 0 !important;
        }

        .order-card.expanded .progress-bar {
            width: var(--final-width) !important;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="orders-wrapper">
        <div class="header-content pt-4">
            <h1 class="playfair">Order History</h1>
            <p class="jakarta">Relive your premium ice cream moments</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                <h2 class="playfair mb-3">No orders placed yet</h2>
                <p class="text-muted mb-4">Your journey of flavors is just one scoop away.</p>
                <a href="index.php" class="btn-premium btn-reorder">Start Exploring</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-meta">
                        <div class="order-info" onclick="toggleCard('<?= $order['id'] ?>')" style="cursor: pointer;">
                            <h3 style="display: flex; align-items: center; gap: 8px;">
                                ORDER #<?= substr($order['id'], 0, 8) ?>
                                <i class="bi bi-chevron-down chevron-icon" style="font-size: 0.8rem; opacity: 0.5;"></i>
                            </h3>
                            <div class="order-date"><?= date('F j, Y • g:i A', strtotime($order['order_date'])) ?></div>
                        </div>
                        <div class="status-badge status-<?= $order['status'] ?>">
                            <?php if($order['status'] === 'pending'): ?>
                                <i class="bi bi-receipt"></i> Processing
                            <?php elseif($order['status'] === 'preparing'): ?>
                                <i class="bi bi-fire"></i> Preparing
                            <?php elseif($order['status'] === 'ready'): ?>
                                <i class="bi bi-bell-fill"></i> Ready
                            <?php elseif($order['status'] === 'completed'): ?>
                                <i class="bi bi-check2-circle"></i> Served
                            <?php else: ?>
                                <i class="bi bi-x-circle"></i> Cancelled
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="collapsible-<?= $order['id'] ?>" class="card-collapsible">
                        <div class="item-list">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item-row">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php 
                                            $img = htmlspecialchars($item['image_url'] ?? 'images/placeholder.png');
                                            if (empty($img)) $img = 'images/placeholder.png';
                                        ?>
                                        <img src="<?= $img ?>" class="item-img" alt="Product">
                                        <div>
                                            <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                            <div class="item-sub">Quantity: <?= $item['quantity'] ?> • <?= number_format($item['price'], 0) ?> MMK</div>
                                        </div>
                                    </div>
                                    <div class="item-price"><?= number_format($item['price'] * $item['quantity'], 0) ?> MMK</div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="stepper-container">
                            <div class="d-flex justify-content-between mb-3 px-1 align-items-center">
                                <span class="fw-800 text-uppercase jakarta" style="font-size: 0.7rem; color: var(--accent-color); letter-spacing: 1.5px;">
                                    <i class="bi bi-radar me-1"></i> Live Tracker
                                </span>
                                <span class="fw-800 jakarta" style="font-size: 0.75rem; color: var(--secondary-text);">
                                    Current Stage: <?= $order['status'] === 'preparing' ? 'Kitchen' : ($order['status'] === 'completed' ? 'Delivered' : ucfirst($order['status'])) ?>
                                </span>
                            </div>
                            
                            <div class="mb-3 px-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size: 0.7rem; font-weight: 700; color: var(--secondary-text);">AVERAGE WAITING TIME</span>
                                    <span style="font-size: 0.7rem; font-weight: 800; color: var(--accent-color);">15 MINS</span>
                                </div>
                                <div class="progress">
                                    <?php 
                                        $progress = min(($order['minutes_elapsed'] / 15) * 100, 100);
                                        if ($order['status'] === 'completed') $progress = 100;
                                    ?>
                                    <div class="progress-bar" role="progressbar" style="--final-width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="position-relative d-flex justify-content-between align-items-center" style="padding: 0 15px;">
                                <div style="position: absolute; top: 50%; left: 0; right: 0; height: 3px; background: rgba(108, 93, 252, 0.1); transform: translateY(-50%); z-index: 1;"></div>
                                <?php 
                                    $statuses = ['pending', 'preparing', 'ready', 'completed'];
                                    $currentIdx = array_search($order['status'], $statuses);
                                    if ($currentIdx === false) $currentIdx = -1;
                                    
                                    $icons = [
                                        'pending' => 'bi-receipt',
                                        'preparing' => 'bi-fire',
                                        'ready' => 'bi-bell',
                                        'completed' => 'bi-check2-circle'
                                    ];
                                    
                                    foreach ($statuses as $idx => $s):
                                        $active = $idx <= $currentIdx;
                                        $glow = $idx === $currentIdx;
                                ?>
                                <div class="step-badge" style="z-index: 2; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: <?= $active ? 'var(--accent-color)' : 'var(--white)' ?>; border: 2.5px solid <?= $active ? 'var(--accent-color)' : 'var(--card-border)' ?>; color: <?= $active ? 'white' : 'var(--secondary-text)' ?>; transition: all 0.5s ease; box-shadow: <?= $glow ? '0 0 20px rgba(108, 93, 252, 0.6)' : 'none' ?>;">
                                    <i class="bi <?= $icons[$s] ?>" style="font-size: 1.1rem;"></i>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex justify-content-between mt-3 px-1" style="font-size: 0.6rem; font-weight: 800; text-transform: uppercase; color: var(--secondary-text); letter-spacing: 0.5px; opacity: 0.7;">
                                <span>Ordered</span>
                                <span>Kitchen</span>
                                <span>Ready</span>
                                <span>Delivered</span>
                            </div>
                        </div>

                        <div id="details-<?= $order['id'] ?>" class="order-details-pane">
                        <div class="details-grid">
                            <div class="detail-item">
                                <h4>Payment Method</h4>
                                <p><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></p>
                            </div>
                            <div class="detail-item">
                                <h4>Service Location</h4>
                                <p><?= !empty($order['table_number']) ? $order['table_number'] : 'Dine-In / Guest' ?></p>
                            </div>
                            <div class="detail-item">
                                <h4>Contact Trace</h4>
                                <p><?= htmlspecialchars($order['phone']) ?></p>
                            </div>
                            <div class="detail-item">
                                <h4>Notes for Artisan</h4>
                                <p><?= !empty($order['notes']) ? htmlspecialchars($order['notes']) : 'No specific requests.' ?></p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top border-secondary-subtle">
                             <div class="d-flex justify-content-between mb-1" style="font-size: 0.9rem; font-weight: 700;">
                                <span>Subtotal</span>
                                <?php 
                                    $deliveryFee = (float)($order['delivery_fee'] ?? 0);
                                    $discount = (float)($order['discount_amount'] ?? 0);
                                    $subtotal = $order['original_subtotal'] ?? ($order['total_price'] - $deliveryFee + $discount);
                                ?>
                                <span><?= number_format($subtotal, 0) ?> MMK</span>
                            </div>
                            <?php if ($discount > 0): ?>
                            <div class="d-flex justify-content-between mb-1" style="font-size: 0.9rem; font-weight: 700; color: var(--accent-color);">
                                <span>Promo Discount (<?= htmlspecialchars($order['coupon_code'] ?? 'SAVINGS') ?>)</span>
                                <span>-<?= number_format($discount, 0) ?> MMK</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div> <!-- end of details-pane -->
                </div> <!-- end of collapsible -->

                <div class="order-footer">
                        <div>
                            <div class="total-label">Final Investment</div>
                            <div class="order-total"><?= number_format($order['total_price'], 0) ?> <span style="font-size: 1rem; font-family: sans-serif;">MMK</span></div>
                        </div>
                        <div class="actions-group">
                            <button class="btn-premium btn-details" onclick="toggleDetails('<?= $order['id'] ?>', this)">View Details</button>
                            <?php if ($order['status'] === 'completed'): ?>
                                <form action="reorder.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn-premium btn-reorder">
                                        <i class="bi bi-arrow-repeat"></i> Reorder
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleCard(orderId) {
            const content = document.getElementById('collapsible-' + orderId);
            const card = content.closest('.order-card');
            const isVisible = content.style.display === 'block';
            
            if (isVisible) {
                content.style.display = 'none';
                card.classList.remove('expanded');
            } else {
                content.style.display = 'block';
                card.classList.add('expanded');
            }
        }

        function toggleDetails(orderId, btn) {
            const detailsBox = document.getElementById('details-' + orderId);
            const content = document.getElementById('collapsible-' + orderId);
            const card = content.closest('.order-card');
            const isVisible = detailsBox.style.display === 'block';
            
            // Ensure card is expanded if trying to see details
            if (!isVisible) {
                content.style.display = 'block';
                card.classList.add('expanded');
            }
            
            if (!btn) {
                const allBtns = document.querySelectorAll('.btn-details');
                for (let b of allBtns) {
                    if (b.getAttribute('onclick') && b.getAttribute('onclick').includes(orderId)) {
                        btn = b;
                        break;
                    }
                }
            }
            
            if (isVisible) {
                detailsBox.style.display = 'none';
                if (btn) btn.innerHTML = 'View Details';
            } else {
                detailsBox.style.display = 'block';
                if (btn) btn.innerHTML = 'Hide Details';
            }
        }

        <?php if ($successMessage): ?>
            Swal.fire({
                icon: 'success',
                title: 'Great Choice!',
                text: '<?= $successMessage ?>',
                confirmButtonColor: '#6c5dfc'
            });
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            Swal.fire({
                icon: 'error',
                title: 'Wait a moment...',
                text: '<?= $errorMessage ?>',
                confirmButtonColor: '#ef4444'
            });
        <?php endif; ?>

    </script>
</body>
</html>
