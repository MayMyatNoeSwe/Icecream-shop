<?php
session_start();
require_once 'config/database.php';

// Debug: Check login success
$showAlert = false;
$userName = 'Guest';
if (isset($_SESSION['login_success'])) {
    $showAlert = true;
    $userName = $_SESSION['user_name'] ?? 'Guest';
    unset($_SESSION['login_success']); // Clear it immediately after reading
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get all products
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM products WHERE quantity > 0 ORDER BY category, name");
    $products = $stmt->fetchAll();
    
    // Group products by category
    $productsByCategory = [];
    foreach ($products as $product) {
        $productsByCategory[$product['category']][] = $product;
    }

    // Handle Edit Mode
    $editItem = null;
    $editIndex = isset($_GET['edit_index']) ? (int)$_GET['edit_index'] : -1;
    if ($editIndex >= 0 && isset($_SESSION['cart'][$editIndex])) {
        $editItem = $_SESSION['cart'][$editIndex];
        // If it's a reorder of a custom item, it might have 'is_reorder' => true
    }
} catch (Exception $e) {
    die("Error loading products: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoops - Premium Artisan Ice Cream</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="cache-buster" content="<?= time() . rand(1000, 9999) ?>">
    <!-- Version: <?= time() ?> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-color: #f7f3ff;
            --primary-text: #2c296d;
            --accent-color: #6c5dfc;
            --secondary-text: #6b6b8d;
            --white: #ffffff;
            --btn-bg: #2c296d;
            --nav-height: 90px;
            --transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            --card-bg: rgba(255, 255, 255, 0.4);
            --card-border: rgba(255, 255, 255, 0.3);
            --nav-bg: rgba(247, 243, 255, 0.8);
        }

        [data-theme="dark"] {
            --bg-color: #0f0f1e;
            --primary-text: #f0f0f5;
            --accent-color: #a78bfa;
            --secondary-text: #c4c4d9;
            --white: #1e1e2f;
            --btn-bg: #7c3aed;
            --card-bg: rgba(30, 30, 47, 0.7);
            --card-border: rgba(167, 139, 250, 0.2);
            --nav-bg: rgba(15, 15, 30, 0.95);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-color);
            color: var(--primary-text);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        /* Luxury Navigation */
        nav {
            height: var(--nav-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(15px);
            background: var(--nav-bg);
            border-bottom: 1px solid rgba(44, 41, 109, 0.05);
            transition: var(--transition);
        }

        .nav-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--primary-text);
            text-decoration: none;
            letter-spacing: -0.02em;
        }

        .nav-links {
            display: flex;
            gap: 3rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--primary-text);
            font-weight: 700;
            font-size: 0.95rem;
            opacity: 0.7;
            transition: var(--transition);
        }

        .nav-links a:hover {
            opacity: 1;
            transform: translateY(-2px);
            color: var(--accent-color);
        }

        .cart-wrapper {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .search-icon {
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.7;
            transition: var(--transition);
        }

        .search-icon:hover {
            opacity: 1;
        }

        /* Theme Toggle Button */
        .theme-toggle {
            background: transparent;
            border: 2px solid var(--primary-text);
            color: var(--primary-text);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            font-size: 1.2rem;
        }

        .theme-toggle:hover {
            transform: translateY(-3px) rotate(20deg);
            background: var(--btn-bg);
            color: var(--white);
            border-color: var(--btn-bg);
            box-shadow: 0 10px 25px rgba(108, 93, 252, 0.3);
        }

        .theme-icon {
            transition: transform 0.3s ease;
        }

        .theme-toggle:active .theme-icon {
            transform: scale(0.8);
        }

        .search-container {
            position: relative;
        }

        .search-box {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%) scale(0.8);
            transform-origin: right center;
            background: white;
            border-radius: 14px;
            padding: 0.5rem 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 10px;
            min-width: 300px;
            border: 2px solid var(--primary-text);
            z-index: 100;
            opacity: 0;
        }

        .search-box.active {
            display: flex;
            animation: searchExpand 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
        }

        .search-box input {
            border: none;
            outline: none;
            padding: 0.5rem;
            font-size: 0.9rem;
            flex: 1;
            background: transparent;
            color: var(--primary-text);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .search-box input::placeholder {
            color: var(--secondary-text);
        }

        .search-close {
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--secondary-text);
            transition: var(--transition);
            padding: 0 5px;
        }

        .search-close:hover {
            color: var(--primary-text);
            transform: rotate(90deg);
        }

        @keyframes searchExpand {
            0% {
                opacity: 0;
                transform: translateY(-50%) scale(0.8);
            }
            100% {
                opacity: 1;
                transform: translateY(-50%) scale(1);
            }
        }

        .product-card.hidden {
            display: none;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary-text);
            font-size: 1.2rem;
            display: none;
        }

        .no-results.show {
            display: block;
        }

        .cart-btn {
            background: var(--btn-bg);
            color: var(--white);
            padding: 0.9rem 2rem;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: var(--transition);
            box-shadow: 0 10px 25px rgba(44, 41, 109, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44, 41, 109, 0.3);
            background: #1e1b52;
        }
        
        .login-btn {
            background: transparent;
            color: var(--primary-text);
            padding: 0.9rem 2rem;
            border: 2px solid var(--primary-text);
            border-radius: 14px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            background: var(--btn-bg);
            color: var(--white);
            border-color: var(--btn-bg);
            box-shadow: 0 10px 25px rgba(44, 41, 109, 0.2);
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-btn {
            background: transparent;
            color: var(--primary-text);
            padding: 0.9rem 2rem;
            border: 2px solid var(--primary-text);
            border-radius: 14px;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .user-btn:hover {
            transform: translateY(-3px);
            background: var(--btn-bg);
            color: var(--white);
            border-color: var(--btn-bg);
            box-shadow: 0 10px 25px rgba(44, 41, 109, 0.2);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 10px;
            background: white;
            min-width: 200px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border-radius: 14px;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid rgba(44, 41, 109, 0.1);
        }

        .dropdown-content.show {
            display: block;
            animation: fadeInDown 0.3s ease;
        }

        .dropdown-content a {
            color: var(--primary-text);
            padding: 15px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .dropdown-content a:hover {
            background: rgba(108, 93, 252, 0.1);
            color: var(--accent-color);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cart-count { 
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
        }

        
        /* Hero Section Premium */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: calc(var(--nav-height) + 2rem) 2rem 2rem;
            position: relative;
        }

        .hero-container {
            max-width: 1300px;
            width: 100%;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 2rem;
            align-items: center;
        }

        .hero-content {
            position: relative;
            z-index: 5;
            padding-left: 2rem;
        }

        .hero-badge {
            display: inline-block;
            background: var(--white);
            padding: 0.7rem 1.4rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--primary-text);
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(44, 41, 109, 0.05);
            position: relative;
            overflow: hidden;
            animation: fadeInDown 0.8s ease-out;
        }

        .hero-badge::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(108, 93, 252, 0.1), transparent);
            animation: shimmer 3s infinite;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 6.5rem;
            line-height: 0.95;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--primary-text);
            letter-spacing: -0.02em;
            position: relative;
            z-index: 2;
            animation: fadeInLeft 1s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .title-accent {
            display: block;
            width: 80px;
            height: 4px;
            background: var(--accent-color);
            margin-top: 1.5rem;
            border-radius: 2px;
            animation: stretch 1s ease-out 0.5s both;
        }

        .vertical-text {
            position: absolute;
            top: 45%;
            left: 2rem;
            transform: translateY(-50%) rotate(-90deg);
            font-size: 6.5rem;
            font-weight: 900;
            color: rgba(44, 41, 109, 0.08);
            letter-spacing: 0.1em;
            z-index: 1;
            pointer-events: none;
            white-space: nowrap;
            text-transform: uppercase;
            mix-blend-mode: multiply;
        }

        [data-theme="dark"] .vertical-text {
            color: rgba(167, 139, 250, 0.1);
            mix-blend-mode: screen;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: var(--secondary-text);
            max-width: 500px;
            margin: 2.5rem 0 3.5rem;
            font-weight: 500;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        .hero-action-group {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .hero-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--btn-bg);
            color: var(--white);
            padding: 1.3rem 2.8rem;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.9rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: var(--transition);
            box-shadow: 0 15px 35px rgba(44, 41, 109, 0.25);
        }

        .hero-cta:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(44, 41, 109, 0.35);
            background: #1e1b52;
        }

        .hero-icons-row {
            display: flex;
            gap: 1.2rem;
        }

        .hero-icon-box {
            width: 52px;
            height: 52px;
            background: var(--white);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.04);
            font-size: 1.3rem;
            transition: var(--transition);
            border: 1px solid rgba(44, 41, 109, 0.05);
        }

        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInRight 1.2s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .hero-image img {
            width: 100%;
            height: auto;
            max-width: 550px;
            filter: drop-shadow(0 40px 80px rgba(0,0,0,0.12));
            z-index: 2;
            transition: var(--transition);
        }

        .bg-circle {
            position: absolute;
            width: 110%;
            height: 110%;
            background: radial-gradient(circle, rgba(108, 93, 252, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 1;
            animation: pulse 6s ease-in-out infinite;
        }

        /* Animations */
        @keyframes shimmer { 0% { left: -100%; } 100% { left: 100%; } }
        @keyframes stretch { from { width: 0; } to { width: 80px; } }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 0.5; } 50% { transform: scale(1.1); opacity: 0.8; } }
        @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-50px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeInRight { from { opacity: 0; transform: translateX(50px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }

        /* Products Section Layout Restored */
        .products-section {
            padding: 80px 0;
            position: relative;
            z-index: 2;
        }
        
        .section-title {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 700;
            color: var(--primary-text);
            margin-bottom: 60px;
        }
        
        .category { 
            margin-bottom: 80px;
        }
        
        .category h2 { 
            color: var(--primary-text);
            margin-bottom: 40px; 
            text-transform: capitalize;
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 600;
            text-align: center;
        }
        
        .products { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr) !important; /* Force exactly 4 columns */
            gap: 25px;
        }
        
        .product-card { 
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px; 
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 560px;
            width: 100%;
            max-width: 350px; /* Prevent cards from getting too wide/narrow */
            margin: 0 auto;
        }
        
        .product-card:hover { 
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(45, 27, 105, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .product-image-container {
            width: 100%;
            height: 280px;
            border-radius: 24px 24px 0 0;
            overflow: hidden;
            position: relative;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
            transition: transform 0.4s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .product-content {
            padding: 30px 30px 45px 30px;
            position: relative;
            z-index: 2;
            height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .product-card h3 { 
            color: var(--primary-text); 
            margin-bottom: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 600;
        }
        
        .product-card p { 
            color: var(--secondary-text); 
            margin-bottom: 20px; 
            font-size: 15px;
            line-height: 1.6;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .price { 
            font-size: 28px; 
            color: var(--primary-text);
            font-weight: 700; 
            font-family: 'Inter', sans-serif;
        }
        
        .stock { 
            color: var(--secondary-text); 
            font-size: 13px;
            padding: 6px 16px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .add-to-cart { 
            background: #2d1b69;
            color: white; 
            border: none; 
            padding: 18px 24px; 
            border-radius: 12px; 
            cursor: pointer; 
            width: 100%;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.2;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(45, 27, 105, 0.3);
            position: relative;
            overflow: hidden;
            margin-top: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            white-space: nowrap;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .add-to-cart::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(45, 27, 105, 0.4);
            background: #5a4fcf;
        }
        
        .add-to-cart:hover::before {
            opacity: 1;
        }
        
        .add-to-cart:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(45, 27, 105, 0.4);
        }

        @media (max-width: 1024px) {
            .hero-container { grid-template-columns: 1fr; text-align: center; }
            .hero-content { padding-left: 0; }
            .hero-title { font-size: 4rem; }
            .vertical-text { display: none; }
            .hero-action-group { justify-content: center; }
            .hero-image { order: -1; }
            .products { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .products { grid-template-columns: 1fr; }
            .hero-title { font-size: 3rem; }
        }
    </style>
</head>
<body>
    <!-- Geometric Shapes -->
    <div class="geometric-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="images/logo-removebg-preview.png" alt="Scoops Logo" style="height: 55px; vertical-align: middle;">
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#flavors">Flavors</a></li>
                <li><a href="story.php">Our Story</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <div class="cart-wrapper">
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
                    <span class="theme-icon">🌙</span>
                </button>
                <div class="search-container">
                    <div class="search-icon" id="searchToggle">🔍</div>
                    <div class="search-box" id="searchBox">
                        <input type="text" id="searchInput" placeholder="Search products..." />
                        <span class="search-close" id="searchClose">✕</span>
                    </div>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            👤 <?= htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]) ?> ▼
                        </button>
                        <div class="dropdown-content">
                            <a href="orders.php">📦 My Orders</a>
                            <a href="logout.php">🚪 Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="login-btn">
                        🔐 Login
                    </a>
                <?php endif; ?>
                <a href="cart.php" class="cart-btn">
                    Cart <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <div class="vertical-text">ICE CREAM</div>
                <div class="hero-badge">Collection 2026</div>
                <h1 class="hero-title">SPECIAL<br>FLAVORS</h1>
                <span class="title-accent"></span>
                <p class="hero-subtitle">Discover the art of frozen luxury. Our handcrafted flavors are composed with the world's finest ingredients to create an unforgettable sensory journey.</p>
                <div class="hero-action-group">
                    <a href="#flavors" class="hero-cta">ORDER NOW</a>
                    <div class="hero-icons-row">
                        <div class="hero-icon-box" title="Handcrafted">🍦</div>
                        <div class="hero-icon-box" title="Natural">🌿</div>
                        <div class="hero-icon-box" title="Global">🌍</div>
                    </div>
                </div>
            </div>
            
            <div class="hero-image">
                <div class="bg-circle"></div>
                <img src="images/matchagreentea-removebg-preview.png" alt="Premium Ice Cream" onerror="this.src='https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=800&q=80'">
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section" id="flavors">
        <div class="container">
            <h2 class="section-title">Our Premium Collection</h2>
            
            <?php foreach ($productsByCategory as $category => $items): ?>
            <div class="category">
                <h2><?= htmlspecialchars($category) ?>s</h2>
                <div class="products">
                <?php foreach ($items as $product): ?>
                <div class="product-card">
                    <?php if (!empty($product['image_url'])): ?>
                        <div class="product-image-container">
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                 class="product-image"
                                 onerror="this.src='https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop'">
                        </div>
                    <?php else: ?>
                        <div class="product-image-container">
                            <img src="https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop" 
                                 alt="Default ice cream" 
                                 class="product-image">
                        </div>
                    <?php endif; ?>
                    <div class="product-content">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-meta">
                            <div class="price"><?= number_format($product['price'], 0) ?> MMK</div>
                            <div class="stock">Stock: <?= $product['quantity'] ?></div>
                        </div>
                        
                        <?php if ($product['category'] === 'flavor'): ?>
                            <button type="button" class="add-to-cart" onclick="openFlavorModal('<?= $product['id'] ?>', '<?= htmlspecialchars($product['name']) ?>', <?= $product['price'] ?>)">
                                Order

                            </button>
                        <?php else: ?>
                            <form method="POST" action="add_to_cart.php">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="add-to-cart">Add to Cart</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </section>

    <!-- Flavor Customization Modal -->
    <div id="flavorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="flavorName">Customize Your Ice Cream</h2>
                <span class="close" onclick="closeFlavorModal()">&times;</span>
            </div>
            
            <form id="customOrderForm" method="POST" action="add_custom_order.php">
                <input type="hidden" id="flavorId" name="flavor_id">
                <input type="hidden" id="flavorPrice" name="flavor_price">
                <input type="hidden" id="editIndex" name="edit_index" value="<?= $editIndex ?>">
                
                <div class="modal-section">
                    <h3>Choose Size (Required)</h3>
                    <div class="size-options">
                        <?php
                        $stmt = $db->query("SELECT * FROM products WHERE category = 'size' AND quantity > 0 ORDER BY price");
                        $sizes = $stmt->fetchAll();
                        foreach ($sizes as $size):
                        ?>
                        <label class="option-card">
                            <input type="radio" name="size_id" value="<?= $size['id'] ?>" data-price="<?= $size['price'] ?>" required>
                            <div class="option-content">
                                <div class="option-name"><?= htmlspecialchars($size['name']) ?></div>
                                <div class="option-price">+<?= number_format($size['price'], 0) ?> MMK</div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="modal-section">
                    <h3>Add Toppings (Optional)</h3>
                    <div class="topping-options">
                        <?php
                        $stmt = $db->query("SELECT * FROM products WHERE category = 'topping' AND quantity > 0 ORDER BY name");
                        $toppings = $stmt->fetchAll();
                        foreach ($toppings as $topping):
                        ?>
                        <label class="option-card">
                            <input type="checkbox" name="toppings[]" value="<?= $topping['id'] ?>" data-price="<?= $topping['price'] ?>">
                            <div class="option-content">
                                <div class="option-name"><?= htmlspecialchars($topping['name']) ?></div>
                                <div class="option-price">+<?= number_format($topping['price'], 0) ?> MMK</div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="total-price">
                        Total: <span id="totalPrice">0</span> MMK
                    </div>
                    <button type="submit" class="modal-add-btn">Add to Cart</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Styles -->
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 5% auto;
            padding: 0;
            border-radius: 24px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 30px;
            border-bottom: 1px solid rgba(45, 27, 105, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            color: #2d1b69;
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            margin: 0;
        }
        
        .close {
            color: #6b46c1;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: #2d1b69;
        }
        
        .modal-section {
            padding: 30px;
            border-bottom: 1px solid rgba(45, 27, 105, 0.1);
        }
        
        .modal-section:last-of-type {
            border-bottom: none;
        }
        
        .modal-section h3 {
            color: #2d1b69;
            font-size: 20px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .size-options, .topping-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .option-card {
            background: rgba(255, 255, 255, 0.5);
            border: 2px solid rgba(45, 27, 105, 0.1);
            border-radius: 16px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
        }
        
        .option-card:hover {
            background: rgba(255, 255, 255, 0.8);
            border-color: #2d1b69;
        }
        
        .option-card input[type="radio"]:checked + .option-content,
        .option-card input[type="checkbox"]:checked + .option-content {
            background: rgba(45, 27, 105, 0.1);
        }
        
        .option-card input {
            display: none;
        }
        
        .option-content {
            border-radius: 12px;
            padding: 10px;
            transition: all 0.3s ease;
        }
        
        .option-name {
            color: #2d1b69;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .option-price {
            color: #6b46c1;
            font-size: 14px;
        }
        
        .modal-footer {
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 0 0 24px 24px;
        }
        
        .total-price {
            color: #2d1b69;
            font-size: 24px;
            font-weight: 700;
            font-family: 'Playfair Display', serif;
        }
        
        .modal-add-btn {
            background: #2d1b69;
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(45, 27, 105, 0.3);
            min-width: 160px;
        }
        
        .modal-add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(45, 27, 105, 0.4);
            background: #5a4fcf;
        }
        
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .size-options, .topping-options {
                grid-template-columns: 1fr;
            }
            
            .modal-footer {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>

    <script>
        // Force cache refresh
        if (performance.navigation.type !== 1) {
            window.location.reload(true);
        }
        
        let currentFlavorPrice = 0;
        
        function openFlavorModal(flavorId, flavorName, flavorPrice) {
            currentFlavorPrice = flavorPrice;
            document.getElementById('flavorId').value = flavorId;
            document.getElementById('flavorPrice').value = flavorPrice;
            document.getElementById('flavorName').textContent = flavorName;
            document.getElementById('flavorModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            updateTotalPrice();
        }
        
        function closeFlavorModal() {
            document.getElementById('flavorModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('customOrderForm').reset();
            // Clear URL param if editing
            if (window.location.search.includes('edit_index')) {
                window.history.replaceState({}, document.title, window.location.pathname);
                document.getElementById('editIndex').value = -1;
            }
            updateTotalPrice();
        }
        
        function updateTotalPrice() {
            let total = currentFlavorPrice;
            
            // Add size price
            const selectedSize = document.querySelector('input[name="size_id"]:checked');
            if (selectedSize) {
                total += parseInt(selectedSize.dataset.price);
            }
            
            // Add topping prices
            const selectedToppings = document.querySelectorAll('input[name="toppings[]"]:checked');
            selectedToppings.forEach(topping => {
                total += parseInt(topping.dataset.price);
            });
            
            document.getElementById('totalPrice').textContent = total.toLocaleString();
        }
        
        // Add event listeners for price updates
        document.addEventListener('DOMContentLoaded', function() {
            // Theme toggle functionality
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.querySelector('.theme-icon');
            const html = document.documentElement;
            
            // Check for saved theme preference or default to light mode
            const currentTheme = localStorage.getItem('theme') || 'light';
            html.setAttribute('data-theme', currentTheme);
            themeIcon.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
            
            themeToggle.addEventListener('click', function() {
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                themeIcon.textContent = newTheme === 'dark' ? '☀️' : '🌙';
            });
            
            const sizeInputs = document.querySelectorAll('input[name="size_id"]');
            const toppingInputs = document.querySelectorAll('input[name="toppings[]"]');
            
            sizeInputs.forEach(input => {
                input.addEventListener('change', updateTotalPrice);
            });
            
            toppingInputs.forEach(input => {
                input.addEventListener('change', updateTotalPrice);
            });
            
            // Search functionality
            const searchToggle = document.getElementById('searchToggle');
            const searchBox = document.getElementById('searchBox');
            const searchInput = document.getElementById('searchInput');
            const searchClose = document.getElementById('searchClose');
            const productCards = document.querySelectorAll('.product-card');
            
            if (searchToggle && searchBox) {
                searchToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    searchBox.classList.toggle('active');
                    if (searchBox.classList.contains('active')) {
                        searchInput.focus();
                    }
                });
                
                searchClose.addEventListener('click', function() {
                    searchBox.classList.remove('active');
                    searchInput.value = '';
                    // Show all products
                    productCards.forEach(card => card.classList.remove('hidden'));
                });
                
                // Search input handler
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    let visibleCount = 0;
                    
                    productCards.forEach(card => {
                        const productName = card.querySelector('h3').textContent.toLowerCase();
                        const productDesc = card.querySelector('p').textContent.toLowerCase();
                        
                        if (productName.includes(searchTerm) || productDesc.includes(searchTerm)) {
                            card.classList.remove('hidden');
                            visibleCount++;
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                    
                    // Show/hide no results message
                    document.querySelectorAll('.category').forEach(category => {
                        const visibleCards = category.querySelectorAll('.product-card:not(.hidden)');
                        let noResultsMsg = category.querySelector('.no-results');
                        
                        if (!noResultsMsg) {
                            noResultsMsg = document.createElement('div');
                            noResultsMsg.className = 'no-results';
                            noResultsMsg.textContent = 'No products found';
                            category.querySelector('.products').appendChild(noResultsMsg);
                        }
                        
                        if (visibleCards.length === 0 && searchTerm !== '') {
                            noResultsMsg.classList.add('show');
                        } else {
                            noResultsMsg.classList.remove('show');
                        }
                    });
                });
                
                // Close search when clicking outside
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.search-container')) {
                        searchBox.classList.remove('active');
                    }
                });
            }
            
            // User dropdown toggle
            const userBtn = document.querySelector('.user-btn');
            const dropdownContent = document.querySelector('.dropdown-content');
            
            if (userBtn && dropdownContent) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownContent.classList.toggle('show');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.user-dropdown')) {
                        dropdownContent.classList.remove('show');
                    }
                });
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('flavorModal');
                if (event.target === modal) {
                    closeFlavorModal();
                }
            });

            // Handle Edit Mode Initialization
            <?php if ($editItem): ?>
                const editFlavorId = '<?= $editItem['flavor_id'] ?>';
                const editSizeId = '<?= $editItem['size_id'] ?>';
                const editToppings = <?= json_encode($editItem['toppings'] ?? []) ?>;
                
                // Find flavor price
                let flavorPrice = 0;
                <?php
                $stmt = $db->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$editItem['flavor_id']]);
                $f = $stmt->fetch();
                if ($f) echo "flavorPrice = " . $f['price'] . ";";
                ?>
                
                openFlavorModal(editFlavorId, '<?= addslashes(explode(' (', $editItem['name'])[0]) ?>', flavorPrice);
                
                // Pre-select size
                const sizeInput = document.querySelector(`input[name="size_id"][value="${editSizeId}"]`);
                if (sizeInput) sizeInput.checked = true;
                
                // Pre-select toppings
                editToppings.forEach(tid => {
                    const toppingInput = document.querySelector(`input[name="toppings[]"][value="${tid}"]`);
                    if (toppingInput) toppingInput.checked = true;
                });
                
                updateTotalPrice();
            <?php endif; ?>
        });
    </script>

    <?php if ($showAlert): ?>
    <script>
        // Debug logging
        console.log('=== LOGIN SUCCESS DETECTED ===');
        console.log('User name: <?= addslashes($userName) ?>');
        console.log('SweetAlert2 loaded:', typeof Swal !== 'undefined');
        
        // Fire immediately when script loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, firing SweetAlert...');
            
            if (typeof Swal === 'undefined') {
                alert('Hi, <?= addslashes(explode(' ', $userName)[0]) ?>! Welcome to Scoops!');
            } else {
                Swal.fire({
                    title: 'Hi, <?= addslashes(explode(' ', $userName)[0]) ?>!',
                    text: 'Welcome to Scoops Premium Artisan Ice Cream',
                    icon: 'success',
                    confirmButtonColor: '#2c296d',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    background: '#f7f3ff',
                    color: '#2c296d'
                }).then(function() {
                    console.log('Alert closed');
                });
            }
        });
    </script>
    <?php echo "<!-- Login success flag was set for user: " . htmlspecialchars($userName) . " -->"; ?>
    <?php endif; ?>

</body>
</html>
