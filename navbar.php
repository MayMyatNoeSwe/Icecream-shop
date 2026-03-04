<?php
// Check login status
$isLoggedIn = isset($_SESSION['user_id']);
?>
<link rel="stylesheet" href="css/navbar.css">
<nav>
    <div class="nav-container">
        <div>

            <a href="index.php" class="logo">
                <img src="images/logo-removebg-preview.png" alt="Scoops Logo">
            </a>
            <button class="mobile-menu-toggle" id="mobileMenuToggle"><span></span><span></span><span></span></button>
        </div>
        <div class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <!-- <li><a href="index.php#flavors">Flavors</a></li> -->
                <li><a href="special-offers.php">Offers</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="orders.php">My Orders</a></li>
                  <button class="search-toggle-btn" id="searchToggleBtn"><i class="bi bi-search"></i> <span class="btn-text">Search</span></button>
            </ul>

        </div>
        <div class="nav-actions">
            <a href="cart.php" class="cart-btn"><i class="bi bi-bag"></i> <span class="btn-text">Cart</span> <span class="cart-count"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></span></a>
            <?php if ($isLoggedIn): ?>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <i class="bi bi-person-circle"></i> 
                        <?= htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]) ?> 
                        <i class="bi bi-chevron-down" style="font-size: 0.8em; margin-left: 5px;"></i>
                    </button>
                    <div class="dropdown-content">
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                            <a href="admin/index.php"><i class="bi bi-speedometer2"></i> Admin Panel</a>
                            <a href="orders.php"><i class="bi bi-box-seam"></i> My Orders</a>
                        <?php else: ?>
                            <a href="orders.php"><i class="bi bi-box-seam"></i> My Orders</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="login-btn"><i class="bi bi-person"></i> <span class="btn-text">Login</span></a>
            <?php endif; ?>
            
             <button class="theme-toggle" id="themeToggle"><span class="theme-icon"><i class="bi bi-moon-stars"></i></span></button>
           
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

