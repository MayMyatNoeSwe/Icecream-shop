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

// Check login status
$isLoggedIn = isset($_SESSION['user_id']);

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        
        /* Hide scrollbars but keep functionality */
        * {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }
        
        *::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none; /* Chrome, Safari, Opera */
        }
        
        html, body {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        html::-webkit-scrollbar, body::-webkit-scrollbar {
            display: none;
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
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.85); /* Slightly clearer */
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            transition: var(--transition);
        }

        [data-theme="dark"] nav {
            background: rgba(15, 15, 30, 0.9);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
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
            gap: 2.5rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--primary-text);
            font-weight: 600;
            font-size: 0.95rem;
            position: relative;
            padding: 5px 0;
            opacity: 0.8;
            transition: var(--transition);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: var(--accent-color);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 2px;
        }

        .nav-links a:hover {
            opacity: 1;
            color: var(--accent-color);
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-toggle-btn {
            background: transparent;
            border: 1px solid rgba(0,0,0,0.08); /* Minimal border */
            color: var(--primary-text);
            padding: 0.7rem 1.4rem; /* Pill shape optimized */
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-toggle-btn:hover {
            background: rgba(108, 93, 252, 0.05);
            color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-1px);
        }

        .search-container {
            position: relative;
        }

        .search-box {
            display: none;
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%) scale(0.8);
            transform-origin: right center;
            background: var(--white);
            border-radius: 14px;
            padding: 0.5rem 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            align-items: center;
            gap: 10px;
            min-width: 300px;
            border: 2px solid var(--primary-text);
            z-index: 1001;
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
            background: transparent;
            border: none;
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

        /* Ensure text is visible in dark mode */
        [data-theme="dark"] .search-box {
            background: rgba(30, 30, 47, 0.95);
            border-color: rgba(167, 139, 250, 0.5);
        }

        [data-theme="dark"] .search-box input {
            color: #f0f0f5;
        }

        [data-theme="dark"] .search-box input::placeholder {
            color: #c4c4d9;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 5px;
            z-index: 1001;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: var(--primary-text);
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
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
            background: rgba(0,0,0,0.03);
            border: 1px solid transparent;
            color: var(--primary-text);
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            font-size: 1.1rem;
        }

        .theme-toggle:hover {
            transform: rotate(15deg);
            background: rgba(108, 93, 252, 0.1);
            color: var(--accent-color);
            box-shadow: none;
        }

        .theme-icon {
            transition: transform 0.3s ease;
        }

        .theme-toggle:active .theme-icon {
            transform: scale(0.8);
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
            background: linear-gradient(135deg, #2c296d 0%, #4c44a1 100%);
            color: var(--white);
            padding: 0.7rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            text-transform: capitalize;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            box-shadow: 0 4px 15px rgba(44, 41, 109, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 41, 109, 0.4);
            filter: brightness(1.1);
        }
        
        .login-btn {
            background: transparent;
            color: var(--primary-text);
            padding: 0.7rem 1.5rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            text-transform: capitalize;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            background: rgba(108, 93, 252, 0.05);
            color: var(--accent-color);
            border-color: var(--accent-color);
            box-shadow: none;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-btn {
            background: transparent;
            color: var(--primary-text);
            padding: 0.7rem 1.4rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            text-transform: capitalize;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .user-btn:hover {
            transform: translateY(-1px);
            background: rgba(108, 93, 252, 0.05);
            color: var(--accent-color);
            border-color: var(--accent-color);
            box-shadow: none;
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
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 25px;
            padding: 0 5%;
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
            max-width: 350px;
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
        

        
        .stock { 
            color: var(--secondary-text); 
            font-size: 11px;
            padding: 4px 12px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50px;
            backdrop-filter: blur(10px);
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
            white-space: nowrap;
        }

        /* Discount Styling */
        .discount-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ff4e50, #f9d423);
            color: #fff;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            z-index: 10;
            box-shadow: 0 10px 25px rgba(255, 78, 80, 0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .old-price {
            font-size: 0.85rem;
            color: var(--secondary-text);
            text-decoration: line-through;
            opacity: 0.5;
            font-weight: 500;
            display: block;
            margin-bottom: -2px;
        }

        .price-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .price { 
            display: flex;
            align-items: baseline;
            gap: 4px;
            font-size: 24px; 
            color: var(--primary-text);
            font-weight: 800; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            line-height: 1;
        }

        .currency {
            font-size: 0.5em;
            font-weight: 700;
            color: var(--secondary-text);
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        .add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(45, 27, 105, 0.4);
            background: #5a4fcf;
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .products { grid-template-columns: repeat(3, 1fr) !important; }
        }

        @media (max-width: 1024px) {
            .hero-container { grid-template-columns: 1fr; text-align: center; }
            .hero-content { padding-left: 0; }
            .hero-title { font-size: 4rem; }
            .vertical-text { display: none; }
            .hero-action-group { justify-content: center; }
            .hero-image { order: -1; }
            .products { grid-template-columns: repeat(2, 1fr) !important; }
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle { display: flex; order: 2; }
            .nav-menu {
                position: fixed; top: var(--nav-height); left: -100%; width: 100%; height: calc(100vh - var(--nav-height));
                background: var(--nav-bg); backdrop-filter: blur(20px); flex-direction: column; align-items: stretch;
                padding: 3rem 2rem; transition: left 0.3s ease; z-index: 1000; overflow-y: auto;
            }
            .nav-menu.active { left: 0; }
            .nav-links { flex-direction: column; width: 100%; margin-bottom: 2.5rem; }
            .nav-links a { display: block; padding: 1.5rem 0; font-size: 1.3rem; }
            .nav-actions { flex-direction: column; width: 100%; }
            .products { grid-template-columns: 1fr !important; }
        }

        /* Reviews Slider Styles */
        .reviews-preview { padding: 80px 5% 180px; position: relative; z-index: 2; overflow: hidden; }
        .reviews-slider-container { position: relative; max-width: 1200px; margin: 0 auto; overflow: hidden; padding: 20px 0; }
        .reviews-track { display: flex; transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1); gap: 30px; }
        .review-preview-card { 
            flex: 0 0 calc(33.333% - 20px); 
            background: var(--card-bg); 
            backdrop-filter: blur(20px); 
            border: 1px solid var(--card-border); 
            padding: 2.5rem; 
            border-radius: 32px; 
            transition: var(--transition);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .review-preview-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(108, 93, 252, 0.1); }
        .review-stars { color: #ffd700; font-size: 1.2rem; margin-bottom: 1.2rem; display: flex; gap: 4px; }
        .review-text { font-style: italic; color: var(--primary-text); margin-bottom: 1.8rem; font-size: 1.1rem; line-height: 1.7; opacity: 0.9; }
        .review-author { font-weight: 800; color: var(--accent-color); font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; }

        .slider-controls { display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 50px; }
        .slider-dot { width: 12px; height: 12px; border-radius: 50%; background: var(--secondary-text); opacity: 0.3; cursor: pointer; transition: var(--transition); }
        .slider-dot.active { background: var(--accent-color); opacity: 1; transform: scale(1.3); }
        
        @media (max-width: 1024px) {
            .review-preview-card { flex: 0 0 calc(50% - 15px); }
        }
        @media (max-width: 768px) {
            .review-preview-card { flex: 0 0 100%; }
        }

        /* Premium SweetAlert Customization */
        .swal-premium-popup {
            border-radius: 32px !important;
            padding: 3rem !important;
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 25px 80px rgba(44, 41, 109, 0.2) !important;
        }
        .swal-premium-title {
            font-family: 'Playfair Display', serif !important;
            font-weight: 900 !important;
            font-size: 2.2rem !important;
            color: #2c1b69 !important;
            margin-bottom: 1rem !important;
        }
        .swal-premium-content {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1.1rem !important;
            line-height: 1.6 !important;
            color: #5d5d8d !important;
        }
        .swal-premium-confirm {
            background: linear-gradient(135deg, #2c296d 0%, #4c44a1 100%) !important;
            border-radius: 16px !important;
            padding: 18px 40px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            font-size: 0.9rem !important;
            box-shadow: 0 10px 30px rgba(44, 41, 109, 0.3) !important;
            transition: all 0.3s ease !important;
        }
        .swal-premium-cancel {
            background: rgba(108, 93, 252, 0.1) !important;
            color: #6c5dfc !important;
            border-radius: 16px !important;
            padding: 18px 40px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            font-size: 0.9rem !important;
            transition: all 0.3s ease !important;
        }
        .swal-premium-confirm:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(44, 41, 109, 0.4) !important; }
        .swal-premium-cancel:hover { background: rgba(108, 93, 252, 0.2) !important; transform: translateY(-3px); }

        /* Modal Styles Restored */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); }
        .modal-content { 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(30px); 
            margin: 5% auto; 
            width: 90%; 
            max-width: 600px; 
            border-radius: 24px; 
            overflow-y: auto; 
            max-height: 90vh; 
            border: 1px solid rgba(255, 255, 255, 0.3); 
            scrollbar-width: none; 
            -ms-overflow-style: none; 
        }
        .modal-content::-webkit-scrollbar { 
            display: none; 
            width: 0; 
            height: 0; 
        }
        .modal-header { 
            padding: 2rem; 
            border-bottom: 1px solid rgba(45, 27, 105, 0.1); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            top: 0; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px); 
            z-index: 10; 
        }
        .modal-header h2 { color: #2d1b69; font-family: 'Playfair Display', serif; font-size: 28px; }
        .close { color: #6b46c1; font-size: 32px; font-weight: bold; cursor: pointer; }
        .modal-section { padding: 2rem; border-bottom: 1px solid rgba(45, 27, 105, 0.1); }
        .option-card { border: 2px solid rgba(45, 27, 105, 0.1); border-radius: 16px; padding: 20px; cursor: pointer; transition: all 0.3s ease; display: block; margin-bottom: 10px; }
        .option-card:hover { border-color: #2d1b69; background: rgba(255, 255, 255, 0.8); }
        .option-card input { display: none; }
        .option-card input:checked + .option-content { background: rgba(45, 27, 105, 0.1); }
        .option-content { border-radius: 12px; padding: 10px; }
        .modal-footer { 
            padding: 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            bottom: 0; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            z-index: 10; 
            border-top: 1px solid rgba(45, 27, 105, 0.1);
        }
        .total-price { color: #2d1b69; font-size: 24px; font-weight: 700; }
        .modal-add-btn { background: #2d1b69; color: white; border: none; padding: 16px 32px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .modal-add-btn:hover { transform: translateY(-2px); background: #5a4fcf; }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="images/logo-removebg-preview.png" alt="Scoops Logo" style="height: 55px; vertical-align: middle;">
            </a>
            <button class="mobile-menu-toggle" id="mobileMenuToggle"><span></span><span></span><span></span></button>
            <div class="nav-menu" id="navMenu">
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#flavors">Flavors</a></li>
                    <li><a href="story.php">Our Story</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="reviews.php">Reviews</a></li>
                </ul>
                <div class="nav-actions">
                    <button class="theme-toggle" id="themeToggle"><span class="theme-icon"><i class="bi bi-moon-stars"></i></span></button>
                    <div class="search-container">
                        <button class="search-toggle-btn" id="searchToggleBtn"><i class="bi bi-search"></i> <span class="btn-text">Search</span></button>
                        <div class="search-box" id="searchBox"><input type="text" id="searchInput" placeholder="Search products..." /><span class="search-close" id="searchClose"><i class="bi bi-x-lg"></i></span></div>
                    </div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="user-dropdown">
                            <button class="user-btn"><i class="bi bi-person-circle"></i> <?= htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]) ?> <i class="bi bi-chevron-down" style="font-size: 0.8em; margin-left: 5px;"></i></button>
                            <div class="dropdown-content">
                                <a href="orders.php"><i class="bi bi-box-seam"></i> My Orders</a>
                                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="login-btn"><i class="bi bi-person"></i> <span class="btn-text">Login</span></a>
                    <?php endif; ?>
                    <a href="cart.php" class="cart-btn"><i class="bi bi-bag"></i> <span class="btn-text">Cart</span> <span class="cart-count"><?= count($_SESSION['cart']) ?></span></a>
                </div>
            </div>
        </div>
    </nav>

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
            <div class="hero-image"><div class="bg-circle"></div><img src="images/matchagreentea-removebg-preview.png" alt="Premium Ice Cream" onerror="this.src='https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=800&q=80'"></div>
        </div>
    </section>

    <section class="products-section" id="flavors">
        <h2 class="section-title">Our Premium Collection</h2>
        <?php foreach ($productsByCategory as $category => $items): ?>
        <div class="category">
            <h2><?= htmlspecialchars($category) ?>s</h2>
            <div class="products">
                <?php foreach ($items as $product): 
                    $now = date('Y-m-d H:i:s');
                    $hasDiscount = false;
                    $discountedPrice = $product['price'];
                    
                    if ($product['discount_percentage'] > 0 && 
                        ($product['discount_start_date'] === null || $product['discount_start_date'] <= $now) && 
                        ($product['discount_end_date'] === null || $product['discount_end_date'] >= $now)) {
                        $hasDiscount = true;
                        $discountedPrice = $product['price'] * (1 - ($product['discount_percentage'] / 100));
                    }
                ?>
                <div class="product-card">
                    <?php if ($hasDiscount): ?>
                        <div class="discount-badge"><?= round($product['discount_percentage']) ?>% OFF</div>
                    <?php endif; ?>
                    <div class="product-image-container">
                        <img src="<?= htmlspecialchars($product['image_url'] ?: 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                    </div>
                    <div class="product-content">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
                        <div class="product-meta">
                            <div class="price-group">
                                <?php if ($hasDiscount): ?>
                                    <span class="old-price"><?= number_format($product['price'], 0) ?></span>
                                    <div class="price"><?= number_format($discountedPrice, 0) ?><span class="currency">MMK</span></div>
                                <?php else: ?>
                                    <div class="price"><?= number_format($product['price'], 0) ?><span class="currency">MMK</span></div>
                                <?php endif; ?>
                            </div>
                            <div class="stock"><?= $product['quantity'] ?> In Stock</div>
                        </div>
                        <?php if ($product['category'] === 'flavor'): ?>
                            <button class="add-to-cart" 
                                data-id="<?= $product['id'] ?>" 
                                data-name="<?= htmlspecialchars($product['name']) ?>" 
                                data-price="<?= $discountedPrice ?>" 
                                onclick="handleOrder(this)">Order</button>
                        <?php else: ?>
                            <?php if ($isLoggedIn): ?>
                                <form method="POST" action="add_to_cart.php"><input type="hidden" name="product_id" value="<?= $product['id'] ?>"><button type="submit" class="add-to-cart">Add to Cart</button></form>
                            <?php else: ?>
                                <button class="add-to-cart" onclick="requireLogin()">Add to Cart</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- Reviews Preview Section -->
    <?php
    try {
        $stmt = $db->query("SELECT * FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 10");
        $previewReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $previewReviews = []; }
    ?>
    <section class="reviews-preview">
        <h2 class="section-title">Customer Love</h2>
        <div class="reviews-slider-container">
            <div class="reviews-track" id="reviewsTrack">
                <?php if (empty($previewReviews)): ?>
                    <p style="text-align: center; width: 100%; opacity: 0.7;">Be the first to leave a review!</p>
                <?php else: ?>
                    <?php foreach ($previewReviews as $review): ?>
                    <div class="review-preview-card">
                        <div class="review-stars"><?php for($i=1; $i<=5; $i++) echo $i <= $review['rating'] ? '★' : '☆'; ?></div>
                        <p class="review-text">"<?= htmlspecialchars($review['comment']) ?>"</p>
                        <div class="review-author">- <?= htmlspecialchars($review['customer_name']) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="slider-controls" id="sliderDots"></div>
        <div style="text-align: center; margin-top: 50px;"><a href="reviews.php" style="color: var(--accent-color); text-decoration: none; font-weight: 800; font-size: 1.1rem; transition: var(--transition);" class="view-all-reviews">View All Reviews →</a></div>
    </section>

    <!-- Flavor Customization Modal -->
    <div id="flavorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="flavorName">Customize Your Ice Cream</h2>
                <span class="close" onclick="closeFlavorModal()">&times;</span>
            </div>
            <form id="customOrderForm" method="POST" action="add_custom_order.php">
                <input type="hidden" id="flavorId" name="flavor_id"><input type="hidden" id="flavorPrice" name="flavor_price">
                <div class="modal-section">
                    <h3>Choose Size (Required)</h3>
                    <div class="size-options">
                        <?php
                        $now = date('Y-m-d H:i:s');
                        $stmt = $db->query("SELECT * FROM products WHERE category = 'size' AND quantity > 0 ORDER BY price");
                        $sizes = $stmt->fetchAll();
                        foreach ($sizes as $i => $size): 
                            $hasDisc = false;
                            $currentPrice = $size['price'];
                            if ($size['discount_percentage'] > 0 && 
                                ($size['discount_start_date'] === null || $size['discount_start_date'] <= $now) && 
                                ($size['discount_end_date'] === null || $size['discount_end_date'] >= $now)) {
                                $hasDisc = true;
                                $currentPrice = $size['price'] * (1 - ($size['discount_percentage'] / 100));
                            }
                        ?>
                        <label class="option-card">
                            <input type="radio" name="size_id" value="<?= $size['id'] ?>" data-price="<?= $currentPrice ?>" <?= $i == 0 ? 'checked' : '' ?> required>
                            <div class="option-content">
                                <div class="option-name"><?= htmlspecialchars($size['name']) ?></div>
                                <div class="option-price">
                                    <?php if ($hasDisc): ?>
                                        <span style="text-decoration: line-through; opacity: 0.5; font-size: 0.8em;">+<?= number_format($size['price'], 0) ?></span> 
                                        +<?= number_format($currentPrice, 0) ?> MMK
                                    <?php else: ?>
                                        +<?= number_format($size['price'], 0) ?> MMK
                                    <?php endif; ?>
                                </div>
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
                            $hasDisc = false;
                            $currentPrice = $topping['price'];
                            if ($topping['discount_percentage'] > 0 && 
                                ($topping['discount_start_date'] === null || $topping['discount_start_date'] <= $now) && 
                                ($topping['discount_end_date'] === null || $topping['discount_end_date'] >= $now)) {
                                $hasDisc = true;
                                $currentPrice = $topping['price'] * (1 - ($topping['discount_percentage'] / 100));
                            }
                        ?>
                        <label class="option-card">
                            <input type="checkbox" name="toppings[]" value="<?= $topping['id'] ?>" data-price="<?= $currentPrice ?>">
                            <div class="option-content">
                                <div class="option-name"><?= htmlspecialchars($topping['name']) ?></div>
                                <div class="option-price">
                                    <?php if ($hasDisc): ?>
                                        <span style="text-decoration: line-through; opacity: 0.5; font-size: 0.8em;">+<?= number_format($topping['price'], 0) ?></span> 
                                        +<?= number_format($currentPrice, 0) ?> MMK
                                    <?php else: ?>
                                        +<?= number_format($topping['price'], 0) ?> MMK
                                    <?php endif; ?>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer"><div class="total-price">Total: <span id="totalPrice">0</span> MMK</div><button type="submit" class="modal-add-btn">Add to Cart</button></div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
        let currentFlavorPrice = 0;
        
        function handleOrder(element) {
            if (!isLoggedIn) {
                requireLogin();
                return;
            }
            const id = element.dataset.id;
            const name = element.dataset.name;
            const price = element.dataset.price;
            openFlavorModal(id, name, price);
        }

        function requireLogin() {
            Swal.fire({
                title: 'Artisan Membership',
                text: 'Join our exclusive club to unlock premium flavors, custom creations, and skip-the-line ordering.',
                imageUrl: 'https://images.unsplash.com/photo-1576506295286-5cda18df43e7?w=400&h=300&fit=crop',
                imageWidth: 400,
                imageHeight: 200,
                imageAlt: 'Premium Scoops',
                showCancelButton: true,
                confirmButtonText: 'Log In Now',
                cancelButtonText: 'Join the Club',
                reverseButtons: true,
                customClass: {
                    popup: 'swal-premium-popup',
                    title: 'swal-premium-title',
                    htmlContainer: 'swal-premium-content',
                    confirmButton: 'swal-premium-confirm',
                    cancelButton: 'swal-premium-cancel'
                },
                buttonsStyling: false,
                showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
                hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = 'register.php';
                }
            });
        }
        function openFlavorModal(flavorId, flavorName, flavorPrice) {
            currentFlavorPrice = parseInt(flavorPrice);
            document.getElementById('flavorId').value = flavorId;
            document.getElementById('flavorPrice').value = flavorPrice;
            document.getElementById('flavorName').textContent = flavorName;
            document.getElementById('flavorModal').style.display = 'block';
            updateTotalPrice();
        }
        function closeFlavorModal() { document.getElementById('flavorModal').style.display = 'none'; }
        function updateTotalPrice() {
            let total = currentFlavorPrice;
            const size = document.querySelector('input[name="size_id"]:checked');
            if (size) total += parseInt(size.dataset.price);
            document.querySelectorAll('input[name="toppings[]"]:checked').forEach(t => total += parseInt(t.dataset.price));
            document.getElementById('totalPrice').textContent = total.toLocaleString();
        }
        document.querySelectorAll('input').forEach(i => i.onchange = updateTotalPrice);
        
        // Theme Toggle
        const themeBtn = document.getElementById('themeToggle');
        themeBtn.onclick = () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const theme = isDark ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            document.querySelector('.theme-icon').textContent = theme === 'dark' ? '☀️' : '🌙';
        };
        if(localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.querySelector('.theme-icon').textContent = '☀️';
        }

        // Mobile Menu
        document.getElementById('mobileMenuToggle').onclick = function() {
            this.classList.toggle('active');
            document.getElementById('navMenu').classList.toggle('active');
        };

        // Search
        const searchBtn = document.getElementById('searchToggleBtn');
        const searchBox = document.getElementById('searchBox');
        searchBtn.onclick = (e) => { e.stopPropagation(); searchBox.classList.toggle('active'); };
        document.getElementById('searchClose').onclick = () => { searchBox.classList.remove('active'); document.getElementById('searchInput').value = ''; filterProducts(''); };
        document.getElementById('searchInput').oninput = (e) => filterProducts(e.target.value);
        function filterProducts(val) {
            document.querySelectorAll('.product-card').forEach(card => {
                card.style.display = card.innerText.toLowerCase().includes(val.toLowerCase()) ? 'block' : 'none';
            });
        }

        // User Dropdown Interaction
        const userBtn = document.querySelector('.user-btn');
        const dropdownContent = document.querySelector('.dropdown-content');
        
        if (userBtn && dropdownContent) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownContent.classList.toggle('show');
            });

            window.addEventListener('click', (e) => {
                if (!e.target.matches('.user-btn') && !e.target.matches('.user-btn *')) {
                    if (dropdownContent.classList.contains('show')) {
                        dropdownContent.classList.remove('show');
                    }
                }
            });
        }

        <?php if ($showAlert): ?>
        Swal.fire({ title: 'Hi, <?= addslashes(explode(' ', $userName)[0]) ?>!', text: 'Welcome to Scoops Creamery', icon: 'success', confirmButtonColor: '#2c296d', timer: 3000 });
        <?php endif; ?>

        // Reviews Slider Logic
        window.addEventListener('load', function() {
            const track = document.getElementById('reviewsTrack');
            const dotsContainer = document.getElementById('sliderDots');
            const cards = document.querySelectorAll('.review-preview-card');
            
            if (cards.length > 0) {
                let currentIndex = 0;
                const cardCount = cards.length;
                
                function getVisibleCards() {
                    if (window.innerWidth <= 768) return 1;
                    if (window.innerWidth <= 1024) return 2;
                    return 3;
                }

                function createDots() {
                    dotsContainer.innerHTML = '';
                    const visible = getVisibleCards();
                    const dotCount = Math.max(1, cardCount - visible + 1);
                    
                    if (dotCount <= 1) {
                        dotsContainer.style.display = 'none';
                        return;
                    }
                    
                    dotsContainer.style.display = 'flex';
                    for (let i = 0; i < dotCount; i++) {
                        const dot = document.createElement('div');
                        dot.className = `slider-dot ${i === currentIndex ? 'active' : ''}`;
                        dot.onclick = () => { currentIndex = i; updateSlider(); resetAutoPlay(); };
                        dotsContainer.appendChild(dot);
                    }
                }

                function updateSlider() {
                    const visible = getVisibleCards();
                    const maxIndex = Math.max(0, cardCount - visible);
                    if (currentIndex > maxIndex) currentIndex = maxIndex;
                    
                    const cardWidth = cards[0].offsetWidth + 30; // Card width + gap
                    track.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
                    
                    // Update dots
                    document.querySelectorAll('.slider-dot').forEach((dot, idx) => {
                        dot.classList.toggle('active', idx === currentIndex);
                    });
                }

                let autoPlay = setInterval(() => {
                    const visible = getVisibleCards();
                    if (cardCount > visible) {
                        currentIndex = (currentIndex + 1) > (cardCount - visible) ? 0 : currentIndex + 1;
                        updateSlider();
                    }
                }, 5000);

                function resetAutoPlay() {
                    clearInterval(autoPlay);
                    autoPlay = setInterval(() => {
                        const visible = getVisibleCards();
                        if (cardCount > visible) {
                            currentIndex = (currentIndex + 1) > (cardCount - visible) ? 0 : currentIndex + 1;
                            updateSlider();
                        }
                    }, 5000);
                }

                window.addEventListener('resize', () => {
                    createDots();
                    updateSlider();
                });

                createDots();
                updateSlider();
            }
        });
    </script>
</body>
</html>
