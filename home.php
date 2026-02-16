<?php
session_start();
require_once 'config/database.php';

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
    <title>Ice Cream Shop - Premium Artisan Ice Cream</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Premium Background Effects */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(120, 119, 198, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(64, 224, 208, 0.08) 0%, transparent 50%);
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            z-index: 1;
        }
        
        /* Premium Floating Elements */
        .geometric-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 2;
        }
        
        .shape {
            position: absolute;
            background: linear-gradient(135deg, rgba(120, 119, 198, 0.1) 0%, rgba(255, 119, 198, 0.05) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .shape:nth-child(1) {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            top: 10%;
            right: 15%;
            animation: premiumFloat 8s ease-in-out infinite;
        }
        
        .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            top: 60%;
            left: 5%;
            animation: premiumFloat 12s ease-in-out infinite reverse;
        }
        
        .shape:nth-child(3) {
            width: 80px;
            height: 80px;
            top: 20%;
            right: 35%;
            border-radius: 20px;
            transform: rotate(45deg);
            animation: premiumFloat 10s ease-in-out infinite;
        }
        
        .shape:nth-child(4) {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            top: 75%;
            right: 25%;
            animation: premiumFloat 6s ease-in-out infinite;
        }
        
        .shape:nth-child(5) {
            width: 100px;
            height: 100px;
            top: 35%;
            left: 8%;
            border-radius: 16px;
            animation: premiumFloat 14s ease-in-out infinite reverse;
        }
        
        @keyframes premiumFloat {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg) scale(1); 
                opacity: 0.3; 
            }
            25% { 
                transform: translateY(-20px) rotate(90deg) scale(1.1); 
                opacity: 0.6; 
            }
            50% { 
                transform: translateY(-40px) rotate(180deg) scale(0.9); 
                opacity: 0.8; 
            }
            75% { 
                transform: translateY(-20px) rotate(270deg) scale(1.05); 
                opacity: 0.5; 
            }
        }
        
        /* Premium Navigation */
        nav { 
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(15, 15, 35, 0.8);
            backdrop-filter: blur(30px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        nav:hover {
            background: rgba(15, 15, 35, 0.95);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-links {
            display: flex;
            gap: 40px;
            list-style: none;
        }
        
        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s ease;
            position: relative;
            padding: 8px 0;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #7c3aed 0%, #ec4899 100%);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover {
            color: rgba(255, 255, 255, 1);
        }
        
        .nav-links a:hover::before {
            width: 100%;
        }
        
        .cart-btn {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #ec4899 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 32px rgba(124, 58, 237, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .cart-btn:hover::before {
            left: 100%;
        }
        
        .cart-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(124, 58, 237, 0.4);
        }
        
        /* Premium Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            z-index: 3;
            padding-top: 100px;
        }
        
        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            align-items: center;
            position: relative;
        }
        
        .hero-content {
            position: relative;
            z-index: 10;
            animation: slideInLeft 1s ease-out;
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 84px;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff 0%, #a855f7 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
            margin-bottom: 30px;
            position: relative;
            animation: titleGlow 2s ease-in-out infinite alternate;
        }
        
        @keyframes titleGlow {
            from { filter: drop-shadow(0 0 20px rgba(168, 85, 247, 0.3)); }
            to { filter: drop-shadow(0 0 40px rgba(236, 72, 153, 0.5)); }
        }
        
        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 150px;
            height: 6px;
            background: linear-gradient(135deg, #7c3aed 0%, #ec4899 100%);
            border-radius: 3px;
            animation: underlineGrow 1.5s ease-out 0.5s both;
        }
        
        @keyframes underlineGrow {
            from { width: 0; }
            to { width: 150px; }
        }
        
        .hero-subtitle {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 50px;
            line-height: 1.7;
            max-width: 450px;
            animation: fadeInUp 1s ease-out 0.3s both;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hero-cta {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #ec4899 100%);
            color: white;
            padding: 20px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
            display: inline-block;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 15px 50px rgba(124, 58, 237, 0.4);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        .hero-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }
        
        .hero-cta:hover::before {
            left: 100%;
        }
        
        .hero-cta:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 25px 70px rgba(124, 58, 237, 0.6);
        }
        
        .hero-image {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            animation: slideInRight 1s ease-out 0.2s both;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .hero-image::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120%;
            height: 120%;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.1) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            border-radius: 50%;
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
            50% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.6; }
        }
        
        .hero-image img {
            max-width: 500px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 30px 60px rgba(0, 0, 0, 0.3));
            position: relative;
            z-index: 2;
            transition: transform 0.4s ease;
        }
        
        .hero-image:hover img {
            transform: scale(1.05) rotate(2deg);
        }
        
        /* Premium Vertical Text */
        .vertical-text {
            position: absolute;
            left: -100px;
            top: 50%;
            transform: translateY(-50%) rotate(-90deg);
            font-family: 'Inter', sans-serif;
            font-size: 56px;
            font-weight: 900;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 12px;
            z-index: 1;
            animation: verticalTextGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes verticalTextGlow {
            from { opacity: 0.3; }
            to { opacity: 0.7; }
        }
        
        /* Premium Social Links */
        .social-links {
            position: absolute;
            bottom: 50px;
            left: 0;
            display: flex;
            gap: 20px;
            z-index: 3;
            animation: fadeInUp 1s ease-out 0.9s both;
        }
        
        .social-links a {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.4s ease;
            font-size: 20px;
        }
        
        .social-links a:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #ec4899 100%);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
        }
        
        .cart-count { 
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 12px; 
            border-radius: 20px; 
            font-weight: 700;
            font-size: 12px;
            min-width: 24px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 20px;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero {
                padding-top: 120px;
            }
            
            .hero-container {
                grid-template-columns: 1fr;
                gap: 60px;
                padding: 0 20px;
                text-align: center;
            }
            
            .hero-title {
                font-size: 56px;
            }
            
            .hero-subtitle {
                max-width: 100%;
            }
            
            .vertical-text {
                display: none;
            }
            
            .social-links {
                position: relative;
                bottom: auto;
                left: auto;
                justify-content: center;
                margin-top: 40px;
            }
            
            .hero-image {
                order: -1;
            }
            
            .hero-image img {
                max-width: 350px;
            }
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
            <div class="logo">
                <img src="images/logo.png" alt="Scoops Logo" style="height: 50px; vertical-align: middle;">
            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#flavors">Flavors</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <a href="cart.php" class="cart-btn">
                🛒 Cart <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-container">
            <div class="hero-content">
                <div class="vertical-text">ICE CREAM</div>
                <div class="hero-badge">LANDING PAGE TEMPLATE</div>
                <h1 class="hero-title">SPECIAL<br>FLAVORS</h1>
                <p class="hero-subtitle">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s.</p>
                <a href="index.php" class="hero-cta">ORDER NOW</a>
                
                <div class="social-links">
                    <a href="#" title="Twitter">🐦</a>
                    <a href="#" title="Instagram">📷</a>
                    <a href="#" title="Facebook">📘</a>
                </div>
            </div>
            
            <div class="hero-image">
                <img src="images/pistachio.webp" alt="Special Ice Cream Flavors" onerror="this.src='https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=500&h=400&fit=crop'">
            </div>
        </div>
    </section>
</body>
</html>