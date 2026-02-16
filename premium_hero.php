<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Flavors - Ice Cream Landing Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            backdrop-filter: blur(10px);
            background: rgba(247, 243, 255, 0.8);
            border-bottom: 1px solid rgba(44, 41, 109, 0.05);
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
            font-weight: 600;
            font-size: 0.95rem;
            opacity: 0.7;
            transition: var(--transition);
        }

        .nav-links a:hover {
            opacity: 1;
            transform: translateY(-2px);
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

        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: calc(var(--nav-height) + 2rem) 2rem 2rem;
            position: relative;
        }

        .container {
            max-width: 1300px;
            width: 100%;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 2rem;
            align-items: center;
        }

        .content {
            position: relative;
            z-index: 5;
            padding-left: 2rem;
        }

        .badge {
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
            animation: fadeInDown 0.8s ease-out;
            position: relative;
            overflow: hidden;
        }

        .badge::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(108, 93, 252, 0.1), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .title-wrapper {
            position: relative;
            margin-bottom: 2.5rem;
        }

        .main-title {
            font-family: 'Playfair Display', serif;
            font-size: 6.5rem;
            line-height: 0.95;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--primary-text);
            animation: fadeInLeft 1s cubic-bezier(0.19, 1, 0.22, 1);
            letter-spacing: -0.02em;
            position: relative;
            z-index: 2;
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

        @keyframes stretch {
            from { width: 0; }
            to { width: 80px; }
        }

        .vertical-text {
            position: absolute;
            top: 45%;
            left: 2rem;
            transform: translateY(-50%) rotate(-90deg);
            font-size: 6.5rem;
            font-weight: 900;
            color: rgba(44, 41, 109, 0.08); /* Slightly more visible like image */
            letter-spacing: 0.1em;
            z-index: 1; /* Overlay on top of background but behind title if title is z-2 */
            pointer-events: none;
            white-space: nowrap;
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-transform: uppercase;
            mix-blend-mode: multiply;
        }

        .description {
            font-size: 1.15rem;
            color: var(--secondary-text);
            max-width: 500px;
            margin-bottom: 3.5rem;
            animation: fadeInUp 1s ease-out 0.2s both;
            font-weight: 500;
        }

        .action-group {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .icons-row {
            display: flex;
            gap: 1.2rem;
        }

        .icon-box {
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

        .icon-box:hover {
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border-color: var(--accent-color);
        }

        .order-btn {
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

        .order-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(44, 41, 109, 0.35);
            background: #1e1b52;
        }

        .image-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInRight 1.2s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .ice-cream-img {
            width: 100%;
            height: auto;
            max-width: 600px;
            filter: drop-shadow(0 40px 80px rgba(0,0,0,0.12));
            z-index: 2;
            transition: var(--transition);
        }

        .image-container:hover .ice-cream-img {
            transform: scale(1.03) rotate(1deg);
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


        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 968px) {
            .container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 3rem;
            }

            .main-title {
                font-size: 4rem;
            }

            .description {
                margin: 0 auto 3rem;
            }

            .action-group {
                align-items: center;
            }

            .vertical-text {
                display: none;
            }

            .image-container {
                order: -1;
            }
        }
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Scoops Logo" style="height: 50px; vertical-align: middle;">
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="#flavors">Special Flavors</a></li>
            <li><a href="#about">Story</a></li>
            <li><a href="#contact">Visit</a></li>
        </ul>
        <div class="cart-wrapper">
            <div class="search-icon">🔍</div>
            <a href="cart.php" class="order-btn" style="padding: 0.8rem 1.8rem; font-size: 0.8rem;">Cart (<?= count($_SESSION['cart']) ?>)</a>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="content">
                <span class="badge">Collection 2026</span>
                <div class="title-wrapper">
                    <h1 class="main-title">Special<br>Flavors</h1>
                    <span class="title-accent"></span>
                    <div class="vertical-text">Ice Cream</div>
                </div>
                <p class="description">
                    Discover the art of frozen luxury. Our handcrafted flavors are composed with the world's finest ingredients to create an unforgettable sensory journey.
                </p>
                <div class="action-group">
                    <a href="index.php" class="order-btn">Order Now</a>
                    <div class="icons-row">
                        <div class="icon-box" title="Handcrafted">🍦</div>
                        <div class="icon-box" title="Natural">🌿</div>
                        <div class="icon-box" title="Global Shipping">🌍</div>
                    </div>
                </div>
            </div>
            <div class="image-container">
                <div class="bg-circle"></div>
                <img src="images/pistachio.webp" alt="Pistachio Luxury Ice Cream" class="ice-cream-img" onerror="this.src='https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=800&q=80'">
            </div>
        </div>
    </section>
</body>
</html>
