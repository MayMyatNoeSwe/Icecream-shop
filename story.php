<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Story - Scoops Premium Artisan Ice Cream</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            line-height: 1.6;
            transition: var(--transition);
        }

        /* Navigation */
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
            color: var(--accent-color);
        }

        .back-btn {
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
        }

        .back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44, 41, 109, 0.3);
        }

        /* Story Container */
        .story-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: calc(var(--nav-height) + 60px) 20px 80px;
        }

        .story-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .story-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            font-weight: 900;
            color: var(--primary-text);
            margin-bottom: 20px;
            line-height: 1.1;
        }

        .story-header p {
            font-size: 1.3rem;
            color: var(--secondary-text);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Story Sections */
        .story-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-bottom: 100px;
            align-items: center;
        }

        .story-section.reverse {
            direction: rtl;
        }

        .story-section.reverse > * {
            direction: ltr;
        }

        .story-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary-text);
            margin-bottom: 20px;
        }

        .story-content p {
            font-size: 1.1rem;
            color: var(--secondary-text);
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .story-image {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .story-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .story-image:hover img {
            transform: scale(1.05);
        }

        /* Values Grid */
        .values-section {
            margin-top: 100px;
            text-align: center;
        }

        .values-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: var(--primary-text);
            margin-bottom: 60px;
        }

        /* Community Gallery */
        .community-section {
            margin-top: 100px;
            text-align: center;
        }

        .community-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: var(--primary-text);
            margin-bottom: 10px;
        }

        .community-subtitle {
            font-size: 1.2rem;
            color: var(--secondary-text);
            margin-bottom: 50px;
        }

        .community-gallery {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 40px;
        }

        .gallery-item {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            aspect-ratio: 1;
            cursor: pointer;
            transition: var(--transition);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 30px 20px 20px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            transform: translateY(0);
        }

        .gallery-overlay p {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-top: 40px;
        }

        .value-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 40px 30px;
            border-radius: 24px;
            transition: var(--transition);
        }

        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(108, 93, 252, 0.2);
        }

        .value-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .value-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary-text);
            margin-bottom: 15px;
        }

        .value-card p {
            color: var(--secondary-text);
            font-size: 1rem;
        }

        /* Timeline */
        .timeline-section {
            margin-top: 100px;
        }

        .timeline-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: var(--primary-text);
            text-align: center;
            margin-bottom: 60px;
        }

        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 100%;
            background: var(--accent-color);
            opacity: 0.3;
        }

        .timeline-item {
            margin-bottom: 60px;
            position: relative;
        }

        .timeline-content {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 30px;
            border-radius: 20px;
            width: 45%;
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: 0;
        }

        .timeline-item:nth-child(even) .timeline-content {
            margin-left: auto;
        }

        .timeline-year {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--accent-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .timeline-content h3 {
            font-size: 1.3rem;
            color: var(--primary-text);
            margin-bottom: 10px;
        }

        .timeline-content p {
            color: var(--secondary-text);
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: var(--accent-color);
            border-radius: 50%;
            top: 30px;
            box-shadow: 0 0 0 8px var(--bg-color);
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            margin-top: 100px;
            padding: 80px 40px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 30px;
        }

        .cta-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: var(--primary-text);
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: var(--secondary-text);
            margin-bottom: 40px;
        }

        .cta-btn {
            display: inline-block;
            background: var(--btn-bg);
            color: var(--white);
            padding: 1.3rem 3rem;
            border-radius: 18px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            transition: var(--transition);
            box-shadow: 0 15px 35px rgba(44, 41, 109, 0.25);
        }

        .cta-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(44, 41, 109, 0.35);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .story-header h1 {
                font-size: 2.5rem;
            }

            .story-section {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .story-section.reverse {
                direction: ltr;
            }

            .values-grid {
                grid-template-columns: 1fr;
            }

            .community-gallery {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .timeline::before {
                left: 20px;
            }

            .timeline-content {
                width: calc(100% - 60px);
                margin-left: 60px !important;
            }

            .timeline-dot {
                left: 20px;
            }

            .nav-links {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .community-gallery {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <img src="images/logo-removebg-preview.png" alt="Scoops Logo" style="height: 55px; vertical-align: middle;">
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#flavors">Flavors</a></li>
                <li><a href="story.php">Our Story</a></li>
            </ul>
            <a href="index.php" class="back-btn">← Back to Shop</a>
        </div>
    </nav>

    <!-- Story Container -->
    <div class="story-container">
        <!-- Header -->
        <div class="story-header">
            <h1>Our Story</h1>
            <p>A journey of passion, flavor, and craftsmanship that began with a simple dream</p>
        </div>

        <!-- Story Section 1 -->
        <div class="story-section">
            <div class="story-content">
                <h2>Where It All Began</h2>
                <p>In 2015, our founder discovered the art of artisan ice cream making during a trip to Italy. Inspired by the rich flavors and traditional techniques, the dream of bringing authentic, handcrafted ice cream to our community was born.</p>
                <p>What started as a small kitchen experiment quickly grew into a passion project, with friends and family becoming our first taste testers and biggest supporters.</p>
            </div>
            <div class="story-image">
                <img src="https://images.unsplash.com/photo-1488900128323-21503983a07e?w=800&q=80" alt="Ice cream making">
            </div>
        </div>

        <!-- Story Section 2 -->
        <div class="story-section reverse">
            <div class="story-content">
                <h2>Crafting Perfection</h2>
                <p>We believe that great ice cream starts with great ingredients. That's why we source only the finest, locally-sourced dairy, fresh fruits, and premium ingredients from around the world.</p>
                <p>Every batch is made in small quantities to ensure the highest quality and freshness. Our artisans spend hours perfecting each flavor, balancing taste, texture, and creativity.</p>
            </div>
            <div class="story-image">
                <img src="https://images.unsplash.com/photo-1501443762994-82bd5dace89a?w=800&q=80" alt="Fresh ingredients">
            </div>
        </div>

        <!-- Story Section 3 -->
        <div class="story-section">
            <div class="story-content">
                <h2>Community First</h2>
                <p>Scoops is more than just an ice cream shop – it's a gathering place for our community. We've hosted countless birthday parties, first dates, and family celebrations.</p>
                <p>We're proud to support local farmers, employ local talent, and give back to the community that has supported us from day one.</p>
            </div>
            <div class="story-image">
                <img src="https://images.unsplash.com/photo-1511895426328-dc8714191300?w=800&q=80" alt="Community gathering">
            </div>
        </div>

        <!-- Community Gallery -->
        <div class="community-section">
            <h2>Our Community</h2>
            <p class="community-subtitle">Moments that make us smile</p>
            <div class="community-gallery">
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1567653418876-5bb0e566e1c2?w=600&q=80" alt="Happy customers">
                    <div class="gallery-overlay">
                        <p>Happy Customers</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=600&q=80" alt="Family enjoying ice cream">
                    <div class="gallery-overlay">
                        <p>Family Time</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=600&q=80" alt="Kids with ice cream">
                    <div class="gallery-overlay">
                        <p>Sweet Memories</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=600&q=80" alt="Birthday celebration">
                    <div class="gallery-overlay">
                        <p>Celebrations</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=600&q=80" alt="Friends together">
                    <div class="gallery-overlay">
                        <p>Friends Forever</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?w=600&q=80" alt="Local events">
                    <div class="gallery-overlay">
                        <p>Community Events</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Values Section -->
        <div class="values-section">
            <h2>Our Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">🌿</div>
                    <h3>Natural Ingredients</h3>
                    <p>We use only natural, high-quality ingredients with no artificial flavors or preservatives.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">🎨</div>
                    <h3>Artisan Craftsmanship</h3>
                    <p>Every batch is handcrafted with care, attention to detail, and years of expertise.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">💚</div>
                    <h3>Sustainability</h3>
                    <p>We're committed to eco-friendly practices and supporting local, sustainable farming.</p>
                </div>
            </div>
        </div>

        <!-- Timeline Section -->
        <div class="timeline-section">
            <h2>Our Journey</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2015</div>
                        <h3>The Dream Begins</h3>
                        <p>First experiments in a home kitchen, perfecting recipes and techniques.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2017</div>
                        <h3>First Shop Opens</h3>
                        <p>Opened our first location with 12 signature flavors and a dream.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2020</div>
                        <h3>Award Winning</h3>
                        <p>Recognized as "Best Artisan Ice Cream" by local food critics.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2024</div>
                        <h3>Going Digital</h3>
                        <p>Launched online ordering to bring our flavors to more customers.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Taste Our Story</h2>
            <p>Experience the passion and craftsmanship in every scoop</p>
            <a href="index.php#flavors" class="cta-btn">Explore Our Flavors</a>
        </div>
    </div>

    <script>
        // Theme support (sync with main site)
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
    </script>
</body>
</html>
