<?php
session_start();
require_once 'config/database.php';

// Get all active coupons
try {
    $db = Database::getInstance()->getConnection();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM coupons WHERE is_active = 1 AND valid_from <= ? AND valid_until >= ? ORDER BY created_at DESC");
    $stmt->execute([$now, $now]);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $coupons = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - Scoops Creamery</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=Slabo+27px&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="public/index.css">
    <style>
        .offers-page {
            padding: 160px 5% 120px;
            background: var(--bg-color);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Premium Floating Decorations */
        .decor {
            position: absolute;
            pointer-events: none;
            opacity: 0.05;
            z-index: 0;
            filter: blur(1px);
            animation: float 20s infinite linear;
        }
        .d1 { top: 15%; left: 5%; font-size: 8rem; transform: rotate(-15deg); }
        .d2 { top: 40%; right: 3%; font-size: 6rem; transform: rotate(10deg); animation-delay: -5s; }
        .d3 { bottom: 10%; left: 10%; font-size: 7rem; transform: rotate(20deg); animation-delay: -10s; }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, 50px) rotate(5deg); }
            66% { transform: translate(-20px, 20px) rotate(-5deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }

        .offers-page::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(rgba(44, 41, 109, 0.04) 1.5px, transparent 1.5px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        .offers-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .offers-header {
            text-align: center;
            margin-bottom: 90px;
            animation: fadeInDown 0.8s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .offers-header h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2rem, 4.5vw, 2.8rem);
            color: var(--primary-text);
            margin-bottom: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .offers-header p {
            color: var(--secondary-text);
            font-size: 1.2rem;
            max-width: 650px;
            margin: 0 auto;
            font-weight: 500;
            line-height: 1.7;
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            width: 100%;
        }

        /* Compact Voucher Gallery Styling */
        .offers-grid .voucher-wrapper {
            width: 100%;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .offers-grid .voucher-wrapper:hover {
            transform: translateY(-20px);
        }

        .offers-grid .voucher-card {
            width: 100% !important;
            max-width: 440px;
            height: 280px;
            margin: 0;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid var(--card-border);
            box-shadow: 
                0 25px 60px rgba(44, 41, 109, 0.05),
                0 10px 20px rgba(0, 0, 0, 0.02);
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
        }

        [data-theme="dark"] .voucher-card {
            background: rgba(30, 30, 47, 0.85) !important;
            border-color: rgba(167, 139, 250, 0.35) !important;
            box-shadow: 
                0 30px 70px rgba(0, 0, 0, 0.5),
                0 0 20px rgba(167, 139, 250, 0.15) !important;
        }

        .offers-grid .voucher-card:hover {
            border-color: var(--accent-color);
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 40px 80px rgba(108, 93, 252, 0.2);
        }

        [data-theme="dark"] .offers-grid .voucher-card:hover {
            box-shadow: 0 40px 80px rgba(167, 139, 250, 0.25);
        }

        .offers-grid .voucher-stub {
            width: 140px;
            padding: 20px 15px;
            background: rgba(108, 93, 252, 0.02);
            border-left: 1px dashed var(--card-border);
        }

        [data-theme="dark"] .voucher-stub {
            background: rgba(167, 139, 250, 0.05) !important;
            border-left: 1px dashed rgba(167, 139, 250, 0.3) !important;
        }

        [data-theme="dark"] .ice-cream-text {
            background: linear-gradient(135deg, #ffffff 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            background-clip: text;
            filter: drop-shadow(0 4px 15px rgba(167, 139, 250, 0.4));
        }

        [data-theme="dark"] .voucher-details p,
        [data-theme="dark"] .treat-text {
            color: #d1d1e9 !important;
            opacity: 1 !important;
        }

        [data-theme="dark"] .stub-details p {
            color: #ffffff !important;
        }

        [data-theme="dark"] .stub-details label {
            color: var(--accent-color) !important;
            filter: brightness(1.2);
        }

        [data-theme="dark"] .free-label {
            color: #d4af37 !important;
            text-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
        }

        .offers-grid .voucher-main {
            padding: 20px 25px;
            flex: 1.2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .offers-grid .ice-cream-text {
            font-size: 2rem;
            margin-bottom: 5px;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .offers-grid .voucher-value {
            font-size: 1.6rem;
            margin: 5px 0;
            color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .offers-grid .free-label {
            font-size: 0.7rem !important;
            letter-spacing: 3px !important;
            margin-bottom: 2px !important;
        }

        .offers-grid .treat-text {
            font-size: 0.55rem !important;
            padding-top: 8px !important;
            margin-top: 3px !important;
        }

        .offers-grid .voucher-stub {
            width: 140px;
            padding: 20px 15px;
            background: rgba(108, 93, 252, 0.02);
            border-left: 1px dashed var(--card-border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        .offers-grid .copy-btn-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 10px 0;
            background: var(--accent-color);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            margin-top: auto;
        }

        .offers-grid .copy-btn-inner:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 93, 252, 0.35);
        }

        .offers-grid .copy-btn-inner.copied {
            background: #10b981;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        [data-theme="dark"] .copy-btn-inner {
            background: rgba(167, 139, 250, 0.2);
            color: #a78bfa;
            border: 1px solid rgba(167, 139, 250, 0.3);
        }

        [data-theme="dark"] .copy-btn-inner:hover {
            background: rgba(167, 139, 250, 0.4);
            color: #ffffff;
        }

        [data-theme="dark"] .copy-btn-inner.copied {
            background: rgba(16, 185, 129, 0.3);
            border-color: rgba(16, 185, 129, 0.5);
            color: #10b981;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive grid */
        @media (max-width: 1400px) {
            .offers-grid { grid-template-columns: repeat(2, 1fr); gap: 50px; }
        }

        @media (max-width: 900px) {
            .offers-grid { grid-template-columns: 1fr; }
            .offers-grid .voucher-card { max-width: 550px; height: 300px; }
            .offers-grid .voucher-stub { width: 190px; }
        }

        @media (max-width: 600px) {
            .offers-grid .voucher-card { height: auto; flex-direction: column; }
            .offers-grid .voucher-stub { width: 100%; border-left: none; border-top: 1px dashed rgba(212, 175, 55, 0.3); padding: 35px; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="decor d1"><i class="bi bi-ice-cream"></i></div>
    <div class="decor d2"><i class="bi bi-cone-striped"></i></div>
    <div class="decor d3"><i class="bi bi-stars"></i></div>

    <main class="offers-page">
        <div class="offers-container">
            <header class="offers-header">
                <span class="free-label" style="display:inline-block; margin-bottom: 15px; background: rgba(212, 175, 55, 0.1); padding: 5px 15px; border-radius: 50px;">EXCLUSIVE REWARDS</span>
                <h2>Special Offers</h2>
                <p>Unlock delightful discounts and sweet surprises. Simply click the copy button on any voucher below to copy your unique promo code.</p>
            </header>

            <div class="offers-grid">
                <?php if (empty($coupons)): ?>
                    <div class="empty-state">
                        <i class="bi bi-ticket-perforated"></i>
                        <h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.8rem; color: var(--primary-text); margin-bottom: 10px;">No Active Vouchers</h2>
                        <p style="color: var(--secondary-text);">Check back later! We're constantly churning up new ways to treat our members.</p>
                        <a href="index.php" class="login-btn" style="display: inline-flex; margin-top: 30px; padding: 0 2rem;">Back to Shop</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($coupons as $promoData): ?>
                        <div class="voucher-wrapper">
                            <div class="voucher-card">
                                <div class="shimmer-effect"></div>
                                <div class="voucher-main">
                                        <div class="voucher-content">
                                            <div class="free-label">CODE: <?= $promoData['code'] ?></div>
                                            <div class="ice-cream-text">Ice Cream</div>
                                            <div class="voucher-details">
                                                <p>this coupon entitles you to</p>
                                                <div class="voucher-value">
                                                    <?php echo ($promoData['discount_type'] === 'percentage') ? (int)$promoData['discount_value'] . '%' : number_format($promoData['discount_value']) . ' MMK'; ?>
                                                    <span>OFF</span>
                                                </div>
                                                <?php if ($promoData['min_order_amount'] > 0): ?>
                                                <div style="font-size: 0.7rem; font-weight: 700; color: var(--accent-color); margin: 8px 0; letter-spacing: 0.5px;">
                                                    MIN. ORDER: <?= number_format($promoData['min_order_amount']) ?> MMK
                                                </div>
                                                <?php else: ?>
                                                <div style="font-size: 0.7rem; font-weight: 700; color: var(--accent-color); margin: 8px 0; letter-spacing: 0.5px; opacity: 0.6;">
                                                    UNLIMITED DELIGHT
                                                </div>
                                                <?php endif; ?>
                                                <p class="treat-text">A PREMIUM DELIGHT JUST FOR YOU</p>
                                            </div>
                                        </div>
                                    </div>
                                
                                <div class="voucher-stub">
                                    <div class="stub-details">
                                        <p><label>From:</label> Scoops Creamery</p>
                                        <p style="margin-bottom: 0;"><label>Valid Until:</label> <?= date('M d, Y', strtotime($promoData['valid_until'])) ?></p>
                                    </div>
                                    <button class="copy-btn-inner" onclick="copyPromoCode(event, '<?= $promoData['code'] ?>', this)">
                                        <i class="bi bi-copy"></i> Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        function copyPromoCode(event, code, btn = null) {
            if (event) event.stopPropagation();
            
            navigator.clipboard.writeText(code).then(() => {
                if (btn) {
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
                    btn.classList.add('copied');
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('copied');
                    }, 2000);
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Code Copied!',
                    text: 'The promo code "' + code + '" is ready to use at checkout.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: 'var(--bg-color)',
                    color: 'var(--primary-text)',
                    customClass: {
                        popup: 'animated-popup'
                    }
                });
            });
        }
    </script>
</body>
</html>
