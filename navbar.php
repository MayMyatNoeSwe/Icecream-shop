<?php
// Check login status
$isLoggedIn = isset($_SESSION['user_id']);
?>
<style>
        /* Luxury Navigation */
        nav {
            height: var(--nav-height);
            display: flex;
            align-items: center;
            justify-content: center;
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
            max-width: 1400px;
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
            height: 32px;
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
            align-items: center;
            gap: 2.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--primary-text);
            font-weight: 600;
            font-size: 1.05rem;
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
            border: 1px solid rgba(0,0,0,0.08);
            color: var(--primary-text);
            padding: 0 1rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 8px;
            height: 38px; /* Compact height */
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
            padding: 0 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            text-transform: capitalize;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            box-shadow: 0 4px 15px rgba(44, 41, 109, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            height: 38px; /* Compact height */
        }

        .cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 41, 109, 0.4);
            filter: brightness(1.1);
        }
        
        .login-btn {
            background: transparent;
            color: var(--primary-text);
            padding: 0 1.2rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            text-transform: capitalize;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            height: 38px;
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
            padding: 0 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            text-transform: capitalize;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 38px;
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
        }
</style>
<nav>
    <div class="nav-container">
        <a href="index.php" class="logo">
            <img src="images/logo-removebg-preview.png" alt="Scoops Logo">
        </a>
        <button class="mobile-menu-toggle" id="mobileMenuToggle"><span></span><span></span><span></span></button>
        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#flavors">Flavors</a></li>
                <li><a href="story.php">Our Story</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="reviews.php">Reviews</a></li>
            </ul>
            <div class="nav-actions">
                <button class="theme-toggle" id="themeToggle"><span class="theme-icon"><i class="bi bi-moon-stars"></i></span></button>
                <button class="search-toggle-btn" id="searchToggleBtn"><i class="bi bi-search"></i> <span class="btn-text">Search</span></button>
                <?php if ($isLoggedIn): ?>
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
                <a href="cart.php" class="cart-btn"><i class="bi bi-bag"></i> <span class="btn-text">Cart</span> <span class="cart-count"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span></a>
            </div>
        </div>
    </div>
</nav>

<script>
    // Theme Toggle Logic
    const themeBtn = document.getElementById('themeToggle');
    const lightVars = {
        '--bg-color': '#f1efe9',
        '--hero-bg': '#f1efe9',
        '--nav-bg': 'rgba(241, 239, 233, 0.8)',
        '--nav-scrolled-bg': 'rgba(255, 255, 255, 0.85)',
        '--primary-text': '#2c296d',
        '--accent-color': '#6c5dfc',
        '--secondary-text': '#6b6b8d',
        '--white': '#ffffff',
        '--btn-bg': '#2c296d',
        '--card-bg': 'rgba(255, 255, 255, 0.4)',
        '--card-border': 'rgba(255, 255, 255, 0.3)',
    };
    const darkVars = {
        '--bg-color': '#1a1914',
        '--hero-bg': '#1a1914',
        '--nav-bg': 'rgba(26, 25, 20, 0.95)',
        '--nav-scrolled-bg': 'rgba(26, 25, 20, 0.92)',
        '--primary-text': '#f0f0f5',
        '--accent-color': '#a78bfa',
        '--secondary-text': '#c4c4d9',
        '--white': '#1e1e2f',
        '--btn-bg': '#7c3aed',
        '--card-bg': 'rgba(30, 30, 47, 0.7)',
        '--card-border': 'rgba(167, 139, 250, 0.2)',
    };

    function applyTheme(theme) {
        const vars = theme === 'dark' ? darkVars : lightVars;
        const root = document.documentElement;
        root.setAttribute('data-theme', theme);
        Object.entries(vars).forEach(([key, val]) => root.style.setProperty(key, val));
        localStorage.setItem('theme', theme);
        const icon = document.querySelector('.theme-icon i');
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
        // Update user icon label if exists
        const themeLabel = document.querySelector('.theme-icon');
        if (themeLabel && !themeLabel.querySelector('i')) {
            themeLabel.textContent = theme === 'dark' ? '☀️' : '🌙';
        }
    }

    if (themeBtn) {
        themeBtn.onclick = () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            applyTheme(isDark ? 'light' : 'dark');
        };
    }

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') applyTheme('dark');

    // Mobile Menu
    const mobileToggle = document.getElementById('mobileMenuToggle');
    if (mobileToggle) {
        mobileToggle.onclick = function() {
            this.classList.toggle('active');
            document.getElementById('navMenu').classList.toggle('active');
        };
    }

    // User Dropdown
    const navbarUserBtn = document.querySelector('.user-btn');
    const navbarDropdownContent = document.querySelector('.dropdown-content');
    if (navbarUserBtn && navbarDropdownContent) {
        navbarUserBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            navbarDropdownContent.classList.toggle('show');
        });
        window.addEventListener('click', (e) => {
            if (!e.target.matches('.user-btn') && !e.target.matches('.user-btn *')) {
                navbarDropdownContent.classList.remove('show');
            }
        });
    }

    // Navbar Scroll Effect
    window.addEventListener('scroll', function() {
        const nav = document.querySelector('nav');
        if (nav) {
            if (window.scrollY > 50) {
                nav.classList.add('nav-scrolled');
            } else {
                nav.classList.remove('nav-scrolled');
            }
        }
    });

    // Premium Search Logic
    const navbarSearchBtn = document.getElementById('searchToggleBtn');
    if (navbarSearchBtn) {
        navbarSearchBtn.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const productCards = Array.from(document.querySelectorAll('.product-card'));
            
            if (productCards.length > 0) {
                // Live Search on Home Page
                Swal.fire({
                    title: '<span style="font-family:\'Slabo 27px\',serif; font-size:1.3rem;">🔍 Search Flavors</span>',
                    html: `
                        <input id="swalSearchInput" type="text" placeholder="Type a flavor name..."
                            style="width:100%; padding:0.7rem 1.1rem; border:2px solid #6c5dfc; border-radius:12px;
                                   font-family:\'Slabo 27px\',serif; font-size:1rem; outline:none;
                                   background:${isDark ? '#2a2a3d' : '#f9f9f9'}; color:${isDark ? '#f0f0f5' : '#2c296d'};
                                   margin-bottom:1rem; box-sizing:border-box;" />
                        <div id="swalSearchResults" style="max-height:340px; overflow-y:auto; display:flex; flex-direction:column; gap:10px;"></div>
                        <div id="swalNoResults" style="display:none; text-align:center; padding:1.5rem; color:#6b6b8d; font-family:\'Slabo 27px\',serif;">
                            No flavors found 🍦
                        </div>
                    `,
                    showCloseButton: true,
                    showConfirmButton: false,
                    background: isDark ? '#1e1e2f' : '#ffffff',
                    color: isDark ? '#f0f0f5' : '#2c296d',
                    width: '520px',
                    customClass: { popup: 'swal-search-popup' },
                    didOpen: () => {
                        const input = document.getElementById('swalSearchInput');
                        const resultsContainer = document.getElementById('swalSearchResults');
                        const noResults = document.getElementById('swalNoResults');

                        function renderResults(term) {
                            resultsContainer.innerHTML = '';
                            const filtered = term === '' ? [] : productCards.filter(card => {
                                const data = (card.getAttribute('data-search') || '').toLowerCase();
                                return data.includes(term.toLowerCase().trim());
                            });

                            if (term === '') {
                                noResults.style.display = 'none';
                                return;
                            }

                            if (filtered.length === 0) {
                                noResults.style.display = 'block';
                                return;
                            }

                            noResults.style.display = 'none';
                            filtered.forEach(card => {
                                const img = card.querySelector('img');
                                const name = card.querySelector('h3');
                                const category = card.querySelector('.product-category, .category-tag') ||
                                                 card.closest('.category')?.querySelector('h2');
                                const price = card.querySelector('.price');

                                const item = document.createElement('div');
                                item.style.cssText = `display:flex; align-items:center; gap:14px; padding:10px 14px;
                                    border-radius:14px; cursor:pointer; transition:background 0.2s;
                                    background:${isDark ? 'rgba(255,255,255,0.05)' : 'rgba(108,93,252,0.04)'};
                                    border:1px solid ${isDark ? 'rgba(167,139,250,0.15)' : 'rgba(108,93,252,0.1)'};`;
                                item.onmouseenter = () => item.style.background = isDark ? 'rgba(167,139,250,0.15)' : 'rgba(108,93,252,0.1)';
                                item.onmouseleave = () => item.style.background = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(108,93,252,0.04)';

                                item.innerHTML = `
                                    ${img ? `<img src="${img.src}" style="width:52px;height:52px;object-fit:cover;border-radius:10px;flex-shrink:0;" />` : '<div style="width:52px;height:52px;background:#eee;border-radius:10px;flex-shrink:0;"></div>'}
                                    <div style="flex:1; text-align:left;">
                                        <div style="font-family:\'Slabo 27px\',serif; font-weight:700; font-size:0.95rem; color:${isDark ? '#f0f0f5' : '#2c296d'};">${name ? name.innerText : ''}</div>
                                        ${category ? `<div style="font-size:0.75rem; color:#6c5dfc; font-weight:600; margin-top:2px;">${category.innerText}</div>` : ''}
                                        ${price ? `<div style="font-size:0.85rem; color:${isDark ? '#c4c4d9' : '#6b6b8d'}; margin-top:2px;">${price.innerText}</div>` : ''}
                                    </div>
                                    <span style="color:#6c5dfc; font-size:1.1rem;">→</span>
                                `;

                                item.addEventListener('click', () => {
                                    Swal.close();
                                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    card.style.transition = 'box-shadow 0.4s ease';
                                    card.style.boxShadow = '0 0 0 3px #6c5dfc66';
                                    setTimeout(() => card.style.boxShadow = '', 1800);
                                });

                                resultsContainer.appendChild(item);
                            });
                        }

                        input.focus();
                        input.addEventListener('input', e => renderResults(e.target.value));
                    }
                });
            } else {
                // Fallback for other pages
                Swal.fire({
                    title: 'Search Flavors',
                    input: 'text',
                    inputPlaceholder: 'Type a flavor name...',
                    showCloseButton: true,
                    confirmButtonText: 'Search',
                    confirmButtonColor: '#6c5dfc'
                }).then((result) => {
                    if (result.value) {
                        window.location.href = 'index.php?search=' + encodeURIComponent(result.value);
                    }
                });
            }
        });
    }
</script>

