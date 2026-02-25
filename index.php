<?php
date_default_timezone_set('Asia/Yangon');
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

// Initialize database connection
try {
    $db = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage());
}

// Handle edit cart item
$editMode = false;
$editFlavorId = null;
$editSizeId = null;
$editToppings = [];
$editFlavorName = '';
$editFlavorPrice = 0;

if (isset($_GET['edit_cart']) && isset($_GET['flavor_id'])) {
    $editIndex = (int)$_GET['edit_cart'];
    $editFlavorId = $_GET['flavor_id'];
    $editSizeId = $_GET['size_id'] ?? null;
    $editToppings = !empty($_GET['toppings']) ? explode(',', $_GET['toppings']) : [];
    
    // Get flavor details
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$editFlavorId]);
        $editFlavor = $stmt->fetch();
        if ($editFlavor) {
            $editFlavorName = $editFlavor['name'];
            // Calculate discounted price if applicable
            $editFlavorPrice = $editFlavor['price'];
            if ($editFlavor['discount_percentage'] > 0) {
                $editFlavorPrice = $editFlavor['price'] * (1 - ($editFlavor['discount_percentage'] / 100));
            }
            $editMode = true;
            
            // Remove the item from cart
            if (isset($_SESSION['cart'][$editIndex])) {
                unset($_SESSION['cart'][$editIndex]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
            }
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

// Get all products
try {
    $stmt = $db->query("SELECT * FROM products WHERE quantity > 0 ORDER BY category, name");
    $all_products = $stmt->fetchAll();
    
    $discountedFlavors = [];
    $featuredFlavors = [];
    $popularFlavors = [];
    $productsByCategory = [];
    
    foreach ($all_products as $product) {
        if ($product['category'] === 'flavor') {
            if ($product['discount_percentage'] > 0) {
                $discountedFlavors[] = $product;
            } elseif ($product['is_featured'] == 1) {
                $featuredFlavors[] = $product;
            } else {
                $popularFlavors[] = $product;
            }
        } else {
            $productsByCategory[$product['category']][] = $product;
        }
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
    <link rel="icon" type="image/png" href="images/logo-removebg-preview.png">
    <link rel="shortcut icon" type="image/png" href="images/logo-removebg-preview.png">
    <link href="https://fonts.googleapis.com/css2?family=Boogaloo&family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Slabo+27px&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="cache-buster" content="<?= time() . rand(1000, 9999) ?>">
    <!-- Version: <?= time() ?> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-color: #f1efe9;
            --primary-text: #2c296d;
            --accent-color: #6c5dfc;
            --secondary-text: #6b6b8d;
            --white: #ffffff;
            --btn-bg: #2c296d;
            --nav-height: 75px;
            --transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            --card-bg: rgba(255, 255, 255, 0.4);
            --card-border: rgba(255, 255, 255, 0.3);
            --nav-bg: rgba(241, 239, 233, 0.8);
            --hero-bg: #f1efe9;
            --nav-scrolled-bg: rgba(255, 255, 255, 0.85);
        }

        [data-theme="dark"] {
            --bg-color: #1a1914;
            --primary-text: #f0f0f5;
            --accent-color: #a78bfa;
            --secondary-text: #c4c4d9;
            --white: #1e1e2f;
            --btn-bg: #7c3aed;
            --card-bg: rgba(30, 30, 47, 0.7);
            --card-border: rgba(167, 139, 250, 0.2);
            --nav-bg: rgba(26, 25, 20, 0.95);
            --hero-bg: #1a1914;
            --nav-scrolled-bg: rgba(26, 25, 20, 0.92);
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
            backdrop-filter: none;
            background: var(--hero-flavor-light, var(--hero-bg));
            border-bottom: none;
            transition: all 0.4s ease;
            font-family: 'Slabo 27px', serif;
        }

        nav.nav-scrolled {
            backdrop-filter: blur(20px);
            background: var(--nav-scrolled-bg);
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        }

        [data-theme="dark"] nav {
            background: var(--hero-flavor-dark, var(--hero-bg));
        }

        [data-theme="dark"] nav.nav-scrolled {
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            background: var(--nav-scrolled-bg);
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
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
        }

        .logo:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }

        .logo img {
            height: 40px;
            width: auto;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--primary-text);
            letter-spacing: -0.02em;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--primary-text);
            font-weight: 600;
            font-size: 1.15rem;
            position: relative;
            padding: 3px 0;
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
            gap: 1.5rem;
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
            padding: 0.6rem 1.2rem; /* Pill shape optimized */
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            font-family: 'Slabo 27px', serif;
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
            width: 36px;
            height: 36px;
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
            padding: 0.6rem 1.4rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05rem;
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
            padding: 0.6rem 1.4rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05rem;
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
            padding: 0.6rem 1.2rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.05rem;
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

        [data-theme="dark"] .dropdown-content {
            background: #1e1e2f;
            border: 1px solid rgba(167, 139, 250, 0.3);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
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

        [data-theme="dark"] .dropdown-content a:hover {
            background: rgba(167, 139, 250, 0.2);
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
            background: var(--hero-flavor-light, var(--hero-bg));
            transition: background 0.8s cubic-bezier(0.19, 1, 0.22, 1);
        }

        [data-theme="dark"] .hero {
            background: var(--hero-flavor-dark, var(--hero-bg));
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
            font-family: 'Boogaloo', cursive;
            font-size: 5.5rem;
            line-height: 1.05;
            font-weight: 400;
            text-transform: uppercase;
            color: var(--primary-text);
            letter-spacing: 0.02em;
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

        /* Flavor Thumbnail Cards */
        .hero-flavor-thumbs {
            display: flex;
            gap: 1rem;
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .flavor-thumb {
            width: 70px;
            height: 70px;
            background: var(--white);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            border: 2.5px solid transparent;
            overflow: hidden;
            position: relative;
        }

        .flavor-thumb img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            transition: transform 0.4s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .flavor-thumb:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 30px rgba(108, 93, 252, 0.2);
            border-color: var(--accent-color);
        }

        .flavor-thumb:hover img {
            transform: scale(1.15);
        }

        .flavor-thumb.active {
            border-color: var(--accent-color);
            box-shadow: 0 8px 25px rgba(108, 93, 252, 0.3), 0 0 0 4px rgba(108, 93, 252, 0.1);
            background: linear-gradient(135deg, rgba(108, 93, 252, 0.05), rgba(108, 93, 252, 0.12));
        }

        .flavor-thumb.active img {
            transform: scale(1.1);
        }

        [data-theme="dark"] .flavor-thumb {
            background: rgba(30, 30, 47, 0.7);
            border-color: transparent;
        }

        [data-theme="dark"] .flavor-thumb:hover,
        [data-theme="dark"] .flavor-thumb.active {
            border-color: var(--accent-color);
            box-shadow: 0 8px 25px rgba(167, 139, 250, 0.3), 0 0 0 4px rgba(167, 139, 250, 0.1);
            background: rgba(167, 139, 250, 0.1);
        }

        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInRight 1.2s cubic-bezier(0.19, 1, 0.22, 1);
            -webkit-mask-image: radial-gradient(ellipse 75% 75% at center, black 45%, transparent 72%);
            mask-image: radial-gradient(ellipse 75% 75% at center, black 45%, transparent 72%);
        }

        .hero-image img {
            width: 100%;
            height: auto;
            max-width: 825px;
            z-index: 2;
            transition: all 0.6s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .hero-image img.switching {
            opacity: 0;
            transform: scale(0.85) translateY(20px);
        }

        .hero-image img.switched-in {
            animation: heroImageIn 0.7s cubic-bezier(0.19, 1, 0.22, 1) forwards;
        }

        @keyframes heroImageIn {
            0% {
                opacity: 0;
                transform: scale(0.85) translateY(20px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .bg-circle {
            position: absolute;
            width: 110%;
            height: 110%;
            background: radial-gradient(circle, rgba(108, 93, 252, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 1;
            animation: pulse 6s ease-in-out infinite;
            transition: background 0.6s ease;
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
            margin-bottom: 40px;
        }
        
        .category h2 { 
            color: var(--primary-text);
            margin-bottom: 20px; 
            text-transform: capitalize;
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            font-weight: 600;
            text-align: center;
        }
        
        .products { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 15px;
            padding: 0 20%;
        }
        
        .product-card { 
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 18px; 
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            height: 360px;
            width: 100%;
            max-width: 240px;
            margin: 0 auto;
        }
        
        .product-card:hover { 
            transform: translateY(-6px);
            box-shadow: 0 15px 40px rgba(45, 27, 105, 0.18);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Premium Info Card (Used for Sizes & Toppings) */
        .premium-info-card {
            height: 330px !important; /* Reduced for Sizes/Toppings */
            display: flex;
            flex-direction: column;
            border-radius: 20px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            max-width: 240px;
            cursor: default;
            transition: all 0.4s ease;
        }

        .premium-info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6c5dfc, #a78bfa);
            z-index: 3;
        }

        .premium-info-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 40px rgba(108, 93, 252, 0.18);
            border-color: rgba(108, 93, 252, 0.3);
        }

        .premium-info-card .product-image-container {
            width: 100%;
            height: 170px;
            min-height: 170px;
            border-radius: 0;
            background: rgba(108, 93, 252, 0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .premium-info-card .product-image {
            width: 110px;
            height: 110px;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .premium-info-card:hover .product-image {
            transform: scale(1.08) translateY(-4px);
        }

        .premium-info-card .product-content {
            padding: 14px 18px 16px;
            height: auto;
            flex: 1;
            justify-content: flex-start;
            gap: 4px;
        }

        .premium-info-card h3 {
            font-size: 16px !important;
            margin-bottom: 4px !important;
            font-family: 'Playfair Display', serif;
            color: var(--primary-text);
        }

        .premium-info-card p {
            font-size: 12.5px !important;
            margin-bottom: 0 !important;
            color: var(--secondary-text);
            line-height: 1.5;
        }

        .info-card-badge {
            display: none;
        }

        [data-theme="dark"] .info-card-badge {
            background: rgba(167, 139, 250, 0.12);
            border-color: rgba(167, 139, 250, 0.3);
            color: #a78bfa;
        }

        /* Sizes category: 3 wide cards = same width as 4 flavor cards */
        .sizes-grid {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 15px !important; /* Updated to 15px */
        }

        .sizes-grid .premium-info-card {
            height: 320px !important; /* Updated to 320px */
            max-width: 320px !important;
            margin: 0 auto;
            width: 100%;
        }

        .sizes-grid .premium-info-card .product-image-container {
            height: 230px;
            min-height: 230px;
            box-shadow: inset 0 0 60px rgba(108, 93, 252, 0.03);
        }

        [data-theme="dark"] .sizes-grid .premium-info-card .product-image-container {
            box-shadow: inset 0 0 60px rgba(167, 139, 250, 0.05);
        }

        .sizes-grid .premium-info-card .product-image {
            width: 190px;
            height: 190px;
        }

        /* Toppings category: 4 cards in a row, big image just like normal */
        .toppings-grid {
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 15px !important;
        }

        .toppings-grid .premium-info-card {
            height: 330px !important;
            max-width: 100% !important;
        }

        .toppings-grid .premium-info-card .product-image-container {
            height: 240px;
            min-height: 240px;
            background: transparent;
        }

        .toppings-grid .premium-info-card .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-image-container {
            width: 100%;
            height: 185px;
            border-radius: 18px 18px 0 0;
            overflow: hidden;
            position: relative;
            cursor: pointer;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .product-card:hover .product-image {
            transform: scale(1.1);
        }
        
        .product-content {
            padding: 12px 18px 16px;
            position: relative;
            z-index: 2;
            height: auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        
        .product-card h3 { 
            color: var(--primary-text); 
            margin-bottom: 6px;
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            font-weight: 600;
        }
        
        .product-card p { 
            color: var(--secondary-text); 
            margin-bottom: 6px; 
            font-size: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
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
            top: 12px;
            right: 12px;
            background: linear-gradient(135deg, #ff4e50, #f9d423);
            color: #fff;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.55rem;
            font-weight: 700;
            z-index: 10;
            box-shadow: 0 6px 15px rgba(255, 78, 80, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .featured-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
            z-index: 10;
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 5px;
        }


        .old-price {
            font-size: 0.75rem;
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
            font-size: 16px; 
            color: var(--primary-text);
            font-weight: 800; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            line-height: 1;
        }

        .currency {
            font-size: 0.6em;
            font-weight: 700;
            color: var(--secondary-text);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .add-to-cart { 
            background: #2d1b69;
            color: white; 
            border: none; 
            padding: 10px 16px; 
            border-radius: 10px; 
            cursor: pointer; 
            width: 100%;
            font-weight: 600;
            font-size: 12px;
            line-height: 1.2;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(45, 27, 105, 0.25);
            position: relative;
            overflow: hidden;
            margin-top: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            white-space: nowrap;
            min-height: 36px;
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
            .hero-title { font-size: 3.8rem; }
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
            padding: 2.2rem; 
            border-radius: 32px; 
            transition: var(--transition);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            height: 350px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
        .review-preview-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(108, 93, 252, 0.1); }
        .review-stars { color: #ffd700; font-size: 1.1rem; margin-bottom: 1rem; display: flex; gap: 4px; }
        .review-text { font-style: italic; color: var(--primary-text); margin-bottom: 1.4rem; font-size: 1rem; line-height: 1.6; opacity: 0.9; }
        .review-author { font-weight: 700; color: var(--accent-color); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }

        .slider-controls { display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 50px; }
        .slider-dot { width: 12px; height: 12px; border-radius: 50%; background: var(--secondary-text); opacity: 0.3; cursor: pointer; transition: var(--transition); }
        .slider-dot.active { background: var(--accent-color); opacity: 1; transform: scale(1.3); }
        
        @media (max-width: 1024px) {
            .review-preview-card { flex: 0 0 calc(50% - 15px); }
        }
        @media (max-width: 768px) {
            .review-preview-card { flex: 0 0 100%; }
            .reviews-preview { padding-bottom: 300px; }
            .view-all-reviews { 
                display: inline-block;
                padding: 12px 24px;
                background: rgba(108, 93, 252, 0.1);
                border-radius: 12px;
                margin-top: 20px;
            }
        }

        /* Premium SweetAlert Customization */
        .swal-premium-popup {
            border-radius: 28px !important;
            padding: 0 !important;
            background: linear-gradient(145deg, #ffffff 0%, #f8f6ff 100%) !important;
            backdrop-filter: blur(30px) !important;
            border: 1px solid rgba(108, 93, 252, 0.12) !important;
            box-shadow: 0 40px 100px rgba(44, 41, 109, 0.2), 0 0 0 1px rgba(255,255,255,0.8) inset !important;
            max-width: 420px !important;
            overflow: hidden !important;
        }

        [data-theme="dark"] .swal-premium-popup {
            background: linear-gradient(145deg, #1a1a2e 0%, #1e1e36 100%) !important;
            border: 1px solid rgba(167, 139, 250, 0.2) !important;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5) !important;
        }

        .swal-hero-img {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: cover;
            display: block;
            border-radius: 28px 28px 0 0;
            margin: -25px 0 0 0; /* Pulled up to remove top whitespace */
        }

        .swal-premium-title {
            font-family: 'Boogaloo', cursive !important;
            font-weight: 400 !important;
            font-size: 2rem !important;
            color: #2c296d !important;
            margin: 0.4rem 2rem 0.4rem !important;
            line-height: 1.2 !important;
            letter-spacing: 0.02em !important;
        }

        [data-theme="dark"] .swal-premium-title {
            color: #f0f0f5 !important;
        }

        .swal-premium-content#swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 0.95rem !important;
            line-height: 1.6 !important;
            color: #7a7a9e !important;
            font-weight: 500 !important;
        }

        [data-theme="dark"] .swal-premium-content {
            color: #b0b0d0 !important;
        }

        .swal2-actions {
            flex-direction: column !important;
            gap: 10px !important;
            padding: 0 2rem 2rem !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .swal-premium-confirm {
            background: linear-gradient(135deg, #2c296d 0%, #4a3fb5 100%) !important;
            border-radius: 14px !important;
            padding: 14px 0 !important;
            width: 100% !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1.5px !important;
            font-size: 0.8rem !important;
            box-shadow: 0 8px 25px rgba(44, 41, 109, 0.3) !important;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1) !important;
            border: none !important;
            color: white !important;
        }

        .swal-premium-confirm:hover { 
            transform: translateY(-2px) !important; 
            box-shadow: 0 12px 35px rgba(44, 41, 109, 0.4) !important;
        }

        .swal-premium-cancel {
            background: transparent !important;
            color: #6c5dfc !important;
            border: 1.5px solid rgba(108, 93, 252, 0.25) !important;
            border-radius: 14px !important;
            padding: 12px 0 !important;
            width: 100% !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1.5px !important;
            font-size: 0.75rem !important;
            transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1) !important;
        }

        .swal-premium-cancel:hover { 
            background: rgba(108, 93, 252, 0.08) !important; 
            transform: translateY(-2px) !important;
            border-color: rgba(108, 93, 252, 0.4) !important;
        }

        [data-theme="dark"] .swal-premium-cancel {
            background: transparent !important;
            color: #a78bfa !important;
            border-color: rgba(167, 139, 250, 0.25) !important;
        }

        [data-theme="dark"] .swal-premium-cancel:hover {
            background: rgba(167, 139, 250, 0.1) !important;
        }

        /* Modal Styles Restored */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); align-items: center; justify-content: center; }
        .modal-content { 
            background: #ffffff; 
            backdrop-filter: blur(30px); 
            width: 90%; 
            max-width: 500px; 
            border-radius: 24px; 
            overflow-y: auto; 
            max-height: 85vh; 
            border: 1px solid rgba(108, 93, 252, 0.2); 
            scrollbar-width: none; 
            -ms-overflow-style: none; 
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        [data-theme="dark"] .modal-content {
            background: #1e1e2f;
            border: 1px solid rgba(167, 139, 250, 0.3);
        }

        .modal-content::-webkit-scrollbar { 
            display: none; 
            width: 0; 
            height: 0; 
        }
        .modal-header { 
            padding: 0.8rem 1.5rem; 
            border-bottom: 1px solid rgba(108, 93, 252, 0.15); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            top: 0; 
            background: #ffffff; 
            backdrop-filter: blur(10px); 
            z-index: 10; 
        }

        [data-theme="dark"] .modal-header {
            background: #1e1e2f;
            border-bottom: 1px solid rgba(167, 139, 250, 0.2);
        }

        .modal-header h2 { color: #2c296d; font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; }
        
        [data-theme="dark"] .modal-header h2 {
            color: #f0f0f5;
        }

        .close { color: #6c5dfc; font-size: 28px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; }
        .close:hover { color: #2c296d; transform: rotate(90deg); }
        
        [data-theme="dark"] .close {
            color: #a78bfa;
        }
        
        [data-theme="dark"] .close:hover {
            color: #f0f0f5;
        }

        .modal-section { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(108, 93, 252, 0.1); }
        
        [data-theme="dark"] .modal-section {
            border-bottom: 1px solid rgba(167, 139, 250, 0.15);
        }

        .modal-section h3 { color: #2c296d; font-weight: 700; margin-bottom: 0.8rem; font-size: 1.1rem; }
        
        [data-theme="dark"] .modal-section h3 {
            color: #f0f0f5;
        }

        .size-options, .topping-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .option-card { 
            border: 2px solid rgba(108, 93, 252, 0.2); 
            border-radius: 16px; 
            padding: 5px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            display: block; 
            margin-bottom: 6px; 
            background: #ffffff;
        }

        [data-theme="dark"] .option-card {
            background: rgba(30, 30, 47, 0.5);
            border: 2px solid rgba(167, 139, 250, 0.3);
        }

        .option-card:hover { 
            border-color: #6c5dfc; 
            background: rgba(108, 93, 252, 0.05); 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 93, 252, 0.15);
        }

        [data-theme="dark"] .option-card:hover {
            border-color: #a78bfa;
            background: rgba(167, 139, 250, 0.15);
            box-shadow: 0 4px 12px rgba(167, 139, 250, 0.25);
        }

        .option-card input { display: none; }
        .option-card input:checked + .option-content { 
            background: rgba(108, 93, 252, 0.1); 
            border-radius: 12px;
        }

        [data-theme="dark"] .option-card input:checked + .option-content {
            background: rgba(167, 139, 250, 0.2);
        }

        .option-content { 
            border-radius: 12px; 
            padding: 6px; 
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .topping-image {
            width: 80px;
            height: 80px;
            padding: 0;
            object-fit: cover;
            border-radius: 12px;
            flex-shrink: 0;
            background: rgba(108, 93, 252, 0.04);
            border: 1px solid rgba(108, 93, 252, 0.08);
        }

        [data-theme="dark"] .topping-image {
            background: rgba(167, 139, 250, 0.06);
            border-color: rgba(167, 139, 250, 0.12);
        }
        
        .option-text {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .option-content .option-name { 
            color: #2c296d; 
            font-weight: 700; 
            font-size: 1rem; 
            margin-bottom: 2px;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            letter-spacing: 0.01em;
        }

        [data-theme="dark"] .option-content .option-name {
            color: #f0f0f5;
        }

        .option-content .option-price { 
            color: #6c5dfc; 
            font-weight: 700; 
            font-size: 0.95rem;
            letter-spacing: -0.01em;
        }

        [data-theme="dark"] .option-content .option-price {
            color: #a78bfa;
        }

        .modal-footer { 
            padding: 0.8rem 1.5rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            position: sticky; 
            bottom: 0; 
            background: #ffffff; 
            backdrop-filter: blur(10px);
            z-index: 10; 
            border-top: 1px solid rgba(108, 93, 252, 0.15);
        }

        [data-theme="dark"] .modal-footer {
            background: #1e1e2f;
            border-top: 1px solid rgba(167, 139, 250, 0.2);
        }

        .total-price { 
            color: #2c296d; 
            font-size: 18px; 
            font-weight: 700; 
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        [data-theme="dark"] .total-price {
            color: #f0f0f5;
        }

        .total-price #totalPrice { 
            font-size: 18px; 
            color: #6c5dfc; 
            font-weight: 800;
        }

        [data-theme="dark"] .total-price #totalPrice {
            color: #a78bfa;
        }

        .total-price span { 
            font-size: 0.95rem; 
            color: #2c296d; 
            font-weight: 600;
        }

        [data-theme="dark"] .total-price span {
            color: #f0f0f5;
        }

        .modal-add-btn { 
            background: linear-gradient(135deg, #2c296d 0%, #4c44a1 100%); 
            color: white; 
            border: none; 
            padding: 10px 24px; 
            border-radius: 12px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .modal-add-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(44, 41, 109, 0.4);
        }

        [data-theme="dark"] .modal-add-btn {
            background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);
        }

        [data-theme="dark"] .modal-add-btn:hover {
            box-shadow: 0 8px 20px rgba(167, 139, 250, 0.4);
        }
        
        /* ============================================
           LOADING ANIMATIONS & SKELETON SCREENS
           ============================================ */
        
        /* Page Loader */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .loader-content {
            text-align: center;
        }
        
        .loader-logo {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: white;
            margin-bottom: 2rem;
            animation: fadeInScale 0.6s ease-out;
        }
        
        /* Spinner */
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Skeleton Screen for Products */
        .skeleton-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            height: 560px;
            width: 100%;
            max-width: 350px;
            margin: 0 auto;
            overflow: hidden;
            position: relative;
        }
        
        .skeleton-image {
            width: 100%;
            height: 280px;
            background: linear-gradient(
                90deg,
                rgba(200, 200, 200, 0.2) 0%,
                rgba(220, 220, 220, 0.3) 50%,
                rgba(200, 200, 200, 0.2) 100%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        .skeleton-content {
            padding: 30px;
        }
        
        .skeleton-title {
            height: 28px;
            width: 70%;
            background: linear-gradient(
                90deg,
                rgba(200, 200, 200, 0.2) 0%,
                rgba(220, 220, 220, 0.3) 50%,
                rgba(200, 200, 200, 0.2) 100%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .skeleton-text {
            height: 16px;
            width: 100%;
            background: linear-gradient(
                90deg,
                rgba(200, 200, 200, 0.2) 0%,
                rgba(220, 220, 220, 0.3) 50%,
                rgba(200, 200, 200, 0.2) 100%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .skeleton-text:last-child {
            width: 80%;
        }
        
        .skeleton-price {
            height: 32px;
            width: 40%;
            background: linear-gradient(
                90deg,
                rgba(200, 200, 200, 0.2) 0%,
                rgba(220, 220, 220, 0.3) 50%,
                rgba(200, 200, 200, 0.2) 100%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .skeleton-button {
            height: 50px;
            width: 100%;
            background: linear-gradient(
                90deg,
                rgba(200, 200, 200, 0.2) 0%,
                rgba(220, 220, 220, 0.3) 50%,
                rgba(200, 200, 200, 0.2) 100%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        /* Dark mode skeleton */
        [data-theme="dark"] .skeleton-image,
        [data-theme="dark"] .skeleton-title,
        [data-theme="dark"] .skeleton-text,
        [data-theme="dark"] .skeleton-price,
        [data-theme="dark"] .skeleton-button {
            background: linear-gradient(
                90deg,
                rgba(60, 60, 80, 0.3) 0%,
                rgba(80, 80, 100, 0.4) 50%,
                rgba(60, 60, 80, 0.3) 100%
            );
            background-size: 200% 100%;
        }
        
        /* Smooth Page Transitions */
        .page-transition {
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Product Cards Stagger Animation */
        .product-card {
            opacity: 0;
            animation: slideInUp 0.6s ease-out forwards;
        }
        
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }
        .product-card:nth-child(7) { animation-delay: 0.7s; }
        .product-card:nth-child(8) { animation-delay: 0.8s; }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Cart Update Spinner */
        .cart-updating {
            position: relative;
            pointer-events: none;
            opacity: 0.6;
        }
        
        .cart-updating::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin: -12px 0 0 -12px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        /* Button Loading State */
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        /* Success Animation */
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            border-radius: 50%;
            display: block;
            stroke-width: 3;
            stroke: #48bb78;
            stroke-miterlimit: 10;
            box-shadow: inset 0px 0px 0px #48bb78;
            animation: fillCheckmark 0.4s ease-in-out 0.4s forwards, scaleCheckmark 0.3s ease-in-out 0.9s both;
        }
        
        .checkmark-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            stroke-miterlimit: 10;
            stroke: #48bb78;
            fill: none;
            animation: strokeCheckmark 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        
        .checkmark-check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: strokeCheckmark 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        
        @keyframes strokeCheckmark {
            100% { stroke-dashoffset: 0; }
        }
        
        @keyframes scaleCheckmark {
            0%, 100% { transform: none; }
            50% { transform: scale3d(1.1, 1.1, 1); }
        }
        
        @keyframes fillCheckmark {
            100% { box-shadow: inset 0px 0px 0px 30px #48bb78; }
        }
        
        /* Pulse Animation for Notifications */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .pulse {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <div class="loader-logo">🍦 Scoops</div>
            <div class="spinner"></div>
        </div>
    </div>
    
    <?php include 'navbar.php'; ?>

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
                    <div class="hero-flavor-thumbs">
                        <div class="flavor-thumb active" data-flavor="matcha" data-img="images/matcha-hero.png" data-bg="#f0f0ea" data-bg-dark="#1a1914" data-title="SPECIAL<br>FLAVORS" data-subtitle="Discover the art of frozen luxury. Our handcrafted flavors are composed with the world's finest ingredients to create an unforgettable sensory journey." onclick="switchHeroFlavor(this)">
                            <img src="images/matcha-hero.png" alt="Matcha Green Tea">
                        </div>
                        <div class="flavor-thumb" data-flavor="pistachio" data-img="images/pistachio-hero.png" data-bg="#f2f0ea" data-bg-dark="#1a1914" data-title="PISTACHIO<br>DREAM" data-subtitle="Rich, creamy pistachio crafted from the finest Sicilian nuts. A timeless classic that melts on your tongue with pure elegance." onclick="switchHeroFlavor(this)">
                            <img src="images/pistachio-hero.png" alt="Pistachio">
                        </div>
                        <div class="flavor-thumb" data-flavor="vanilla" data-img="images/vanilla-hero.png" data-bg="#f2f0ec" data-bg-dark="#1c1a15" data-title="VANILLA<br>BLISS" data-subtitle="Pure Madagascar vanilla, slow-churned to creamy perfection. The quintessential flavor elevated to extraordinary heights." onclick="switchHeroFlavor(this)">
                            <img src="images/vanilla-hero.png" alt="Vanilla">
                        </div>
                    </div>
                </div>
            </div>
            <div class="hero-image"><div class="bg-circle"></div><img id="heroMainImg" src="images/matcha-hero.png" alt="Premium Ice Cream"></div>
        </div>
    </section>

    <section class="products-section" id="flavors">
        <?php 
        date_default_timezone_set('Asia/Yangon');
        $now = date('Y-m-d H:i:s');
        
        // Define flavor sections
        $flavorSections = [
            ['title' => 'Discounted Flavors', 'items' => $discountedFlavors, 'badge' => 'OFF'],
            ['title' => 'Featured Collection', 'items' => $featuredFlavors, 'badge' => 'Featured'],
            ['title' => 'Most Popular', 'items' => $popularFlavors, 'badge' => 'Popular']
        ];

        foreach ($flavorSections as $section): 
            if (empty($section['items'])) continue;
        ?>
        <div class="category">
            <h2><?= $section['title'] ?></h2>
            <div class="products">
                <?php foreach ($section['items'] as $product): 
                    $hasDiscount = $product['discount_percentage'] > 0;
                    $discountedPrice = $product['price'] * (1 - ($product['discount_percentage'] / 100));
                ?>
                <div class="product-card" data-search="<?= strtolower(htmlspecialchars($product['name'] . ' ' . $product['description'] . ' ' . $product['category'])) ?>">
                    <?php if ($hasDiscount): ?>
                        <div class="discount-badge"><?= round($product['discount_percentage']) ?>% OFF</div>
                    <?php elseif ($section['badge'] === 'Featured'): ?>
                        <div class="discount-badge" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #000;">FEATURED</div>
                    <?php elseif ($section['badge'] === 'Popular'): ?>
                        <div class="discount-badge" style="background: linear-gradient(135deg, #6c5dfc, #a78bfa);">POPULAR</div>
                    <?php endif; ?>

                    <div class="product-image-container">
                        <img src="<?= htmlspecialchars($product['image_url'] ?: 'images/default-ice-cream.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
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
                        </div>
                        <button class="add-to-cart" 
                            data-id="<?= $product['id'] ?>" 
                            data-name="<?= htmlspecialchars($product['name']) ?>" 
                            data-price="<?= $discountedPrice ?>" 
                            onclick="handleOrder(this)">Order</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php 
        // Render remaining categories (Size, Topping)
        foreach ($productsByCategory as $category => $items): 
            if (empty($items)) continue;
        ?>
        <div class="category">
            <h2><?= htmlspecialchars(ucfirst($category)) ?>s</h2>
            <div class="products<?= strtolower($category) === 'size' ? ' sizes-grid' : (strtolower($category) === 'topping' ? ' toppings-grid' : '') ?>">
                <?php foreach ($items as $product): ?>
                <div class="product-card premium-info-card" data-search="<?= strtolower(htmlspecialchars($product['name'] . ' ' . $product['description'] . ' ' . $product['category'])) ?>">
                    <div class="product-image-container">
                        <img src="<?= htmlspecialchars($product['image_url'] ?: 'images/default-ice-cream.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                    </div>
                    <div class="product-content">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['description']) ?></p>
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
                    <h3>Available Sizes</h3>
                    <div class="size-options">
                        <?php
                        $stmt = $db->query("SELECT * FROM products WHERE category = 'size' AND quantity > 0 ORDER BY price");
                        $sizes = $stmt->fetchAll();
                        foreach ($sizes as $i => $size): 
                            $hasDisc = false;
                            $currentPrice = $size['price'];
                            if ($size['discount_percentage'] > 0) {
                                $hasDisc = true;
                                $currentPrice = $size['price'] * (1 - ($size['discount_percentage'] / 100));
                            }
                        ?>
                        <label class="option-card">
                            <input type="radio" name="size_id" value="<?= $size['id'] ?>" data-price="<?= $currentPrice ?>" <?= $i == 0 ? 'checked' : '' ?> required>
                            <div class="option-content">
                                <?php if (!empty($size['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($size['image_url']) ?>" alt="<?= htmlspecialchars($size['name']) ?>" class="topping-image">
                                <?php endif; ?>
                                <div class="option-text">
                                    <div class="option-name"><?= htmlspecialchars($size['name']) ?></div>
                                    <div class="option-price">
                                        <?php if ($hasDisc): ?>
                                            <span style="text-decoration: line-through; opacity: 0.5; font-size: 0.8em;"><?= number_format($size['price'], 0) ?></span> 
                                            <?= number_format($currentPrice, 0) ?> MMK
                                        <?php else: ?>
                                            <?= number_format($size['price'], 0) ?> MMK
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($size['description'])): ?>
                                        <div style="font-size:0.75rem; opacity:0.6; margin-top:2px;"><?= htmlspecialchars($size['description']) ?></div>
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
                            if ($topping['discount_percentage'] > 0) {
                                $hasDisc = true;
                                $currentPrice = $topping['price'] * (1 - ($topping['discount_percentage'] / 100));
                            }
                        ?>
                        <label class="option-card">
                            <input type="checkbox" name="toppings[]" value="<?= $topping['id'] ?>" data-price="<?= $currentPrice ?>">
                            <div class="option-content">
                                <?php if (!empty($topping['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($topping['image_url']) ?>" alt="<?= htmlspecialchars($topping['name']) ?>" class="topping-image">
                                <?php endif; ?>
                                <div class="option-text">
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
        // Hero Flavor Switcher
        function switchHeroFlavor(thumb) {
            if (thumb.classList.contains('active')) return;

            const heroSection = document.querySelector('.hero');
            const heroImg = document.getElementById('heroMainImg');
            const heroTitle = document.querySelector('.hero-title');
            const heroSubtitle = document.querySelector('.hero-subtitle');
            const newSrc = thumb.dataset.img;
            const newTitle = thumb.dataset.title;
            const newSubtitle = thumb.dataset.subtitle;

            // Remove active from all thumbs
            document.querySelectorAll('.flavor-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');

            // Change hero background color smoothly via CSS variables on root
            // This ensures theme switching works immediately and components like navbar can react
            const root = document.documentElement;
            root.style.setProperty('--hero-flavor-light', thumb.dataset.bg);
            root.style.setProperty('--hero-flavor-dark', thumb.dataset.bgDark);

            // Animate out
            heroImg.classList.add('switching');
            heroImg.classList.remove('switched-in');

            // Fade out title & subtitle
            heroTitle.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            heroSubtitle.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            heroTitle.style.opacity = '0';
            heroTitle.style.transform = 'translateY(-10px)';
            heroSubtitle.style.opacity = '0';
            heroSubtitle.style.transform = 'translateY(10px)';

            setTimeout(() => {
                // Swap image
                heroImg.src = newSrc;
                heroImg.classList.remove('switching');
                heroImg.classList.add('switched-in');

                // Swap text
                heroTitle.innerHTML = newTitle;
                heroSubtitle.textContent = newSubtitle;

                // Fade text back in
                requestAnimationFrame(() => {
                    heroTitle.style.opacity = '1';
                    heroTitle.style.transform = 'translateY(0)';
                    heroSubtitle.style.opacity = '1';
                    heroSubtitle.style.transform = 'translateY(0)';
                });

                // Clean up animation class after it completes
                setTimeout(() => {
                    heroImg.classList.remove('switched-in');
                }, 700);
            }, 350);
        }

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
                html: '<img src="images/matcha-hero.png" class="swal-hero-img" alt="Premium Hero"><h2 class="swal-premium-title">Artisan Membership</h2><p style="margin:1rem 2rem;color:#7a7a9e;font-size:0.9rem;line-height:1.6">Sign in to unlock <strong>premium flavors</strong>, custom creations & skip-the-line ordering.</p>',
                showCancelButton: true,
                confirmButtonText: '🔑 &nbsp;Log In Now',
                cancelButtonText: 'Create Account',
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
            document.getElementById('flavorModal').style.display = 'flex';
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
        
        // Handle edit mode - auto-open modal with pre-selected options
        <?php if ($editMode): ?>
        window.addEventListener('load', function() {
            // Force hide loading overlay
            const loadingOverlay = document.getElementById('editLoadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                loadingOverlay.style.display = 'none';
            }
            
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                // Open the modal
                openFlavorModal('<?= $editFlavorId ?>', '<?= addslashes($editFlavorName) ?>', <?= $editFlavorPrice ?>);
                
                // Pre-select size
                <?php if ($editSizeId): ?>
                const sizeRadio = document.querySelector('input[name="size_id"][value="<?= $editSizeId ?>"]');
                if (sizeRadio) {
                    sizeRadio.checked = true;
                }
                <?php endif; ?>
                
                // Pre-select toppings
                <?php if (!empty($editToppings)): ?>
                <?php foreach ($editToppings as $toppingId): ?>
                const toppingCheckbox = document.querySelector('input[name="toppings[]"][value="<?= $toppingId ?>"]');
                if (toppingCheckbox) {
                    toppingCheckbox.checked = true;
                }
                <?php endforeach; ?>
                <?php endif; ?>
                
                // Update the total price
                updateTotalPrice();
            }, 100);
        });
        <?php endif; ?>

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
        
        // ============================================
        // LOADING ANIMATIONS & PAGE TRANSITIONS
        // ============================================
        
        // Page Loader robust removal
        (function() {
            function hidePageLoader() {
                const pageLoader = document.getElementById('pageLoader');
                if (pageLoader) {
                    setTimeout(() => {
                        pageLoader.classList.add('hidden');
                        setTimeout(() => {
                            pageLoader.style.display = 'none';
                        }, 500);
                    }, 500);
                }
            }

            if (document.readyState === 'complete') {
                hidePageLoader();
            } else {
                window.addEventListener('load', hidePageLoader);
            }
            
            // Fallback: hide anyway after 5 seconds
            setTimeout(hidePageLoader, 5000);
        })();
        
        // Add page transition class to main sections
        document.addEventListener('DOMContentLoaded', function() {
            const hero = document.querySelector('.hero');
            const productsSection = document.querySelector('.products-section');
            
            if (hero) hero.classList.add('page-transition');
            if (productsSection) productsSection.classList.add('page-transition');
        });
        
        // Cart Update Animation
        const addToCartForms = document.querySelectorAll('form[action="add_to_cart.php"]');
        addToCartForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.classList.add('btn-loading');
                    button.disabled = true;
                }
            });
        });
        
        // Custom Order Button Loading
        const customOrderButtons = document.querySelectorAll('.custom-order-btn');
        customOrderButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.add('btn-loading');
            });
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Show success animation when item added to cart
        if (window.location.search.includes('success=added')) {
            Swal.fire({
                icon: 'success',
                title: 'Added to Cart!',
                text: 'Item successfully added to your cart',
                showConfirmButton: false,
                timer: 1500,
                customClass: {
                    popup: 'animated-popup'
                }
            });
            
            // Clean URL
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, '', url);
        }

        // Show loading overlay if in edit mode
        <?php if ($editMode): ?>
        // Hide loading overlay as soon as possible
        (function() {
            function hideOverlay() {
                const loadingOverlay = document.getElementById('editLoadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.style.opacity = '0';
                    loadingOverlay.style.pointerEvents = 'none';
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                    }, 300);
                }
            }
            
            // Try to hide immediately
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hideOverlay);
            } else {
                hideOverlay();
            }
        })();
        <?php endif; ?>
    </script>

    <?php if ($editMode): ?>
    <!-- Loading Overlay for Edit Mode -->
    <div id="editLoadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(247, 243, 255, 0.98); backdrop-filter: blur(10px); z-index: 10000; display: flex; justify-content: center; align-items: center; flex-direction: column; transition: opacity 0.3s ease;">
        <div style="width: 60px; height: 60px; border: 4px solid rgba(108, 93, 252, 0.2); border-top-color: #6c5dfc; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
        <div style="margin-top: 20px; font-size: 1.1rem; color: #2c296d; font-weight: 600;">Loading editor...</div>
    </div>
    <script>
        // Inline script to hide overlay ASAP
        (function() {
            const overlay = document.getElementById('editLoadingOverlay');
            if (overlay) {
                setTimeout(() => {
                    overlay.style.opacity = '0';
                    setTimeout(() => overlay.style.display = 'none', 300);
                }, 500);
            }
        })();
    </script>
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <?php endif; ?>
    <script>
        // Navbar scroll effect
        (function() {
            const nav = document.querySelector('nav');
            const hero = document.querySelector('.hero');
            if (nav && hero) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > hero.offsetHeight - 100) {
                        nav.classList.add('nav-scrolled');
                    } else {
                        nav.classList.remove('nav-scrolled');
                    }
                });
            }
        })();
    </script>
</body>
</html>
