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

// Promo Code Alert System - Show every time for testing
$showPromoAlert = false;
$promoData = null;
try {
    $db = Database::getInstance()->getConnection();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare("SELECT * FROM coupons WHERE is_active = 1 AND valid_from <= ? AND valid_until >= ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$now, $now]);
    $promoData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($promoData) {
        $showPromoAlert = false; // Alert is disabled
    }
} catch (Exception $e) { /* ignore */ }

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public/index.css">
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

    <section class="hero" id="home" style="--hero-flavor-light: #f0f0ea; --hero-flavor-dark: #1a1914;">
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
                            <img src="images/matcha-hero.png" alt="Matcha Green Tea" style="border-radius:10px">
                        </div>
                        <div class="flavor-thumb" data-flavor="pistachio" data-img="images/pistachio-hero.png" data-bg="#f2f0ea" data-bg-dark="#1a1914" data-title="PISTACHIO<br>DREAM" data-subtitle="Rich, creamy pistachio crafted from the finest Sicilian nuts. A timeless classic that melts on your tongue with pure elegance." onclick="switchHeroFlavor(this)">
                            <img src="images/pistachio-hero.png" alt="Pistachio" style="border-radius:10px">
                        </div>
                        <div class="flavor-thumb" data-flavor="vanilla" data-img="images/vanilla-hero.png" data-bg="#f2f0ec" data-bg-dark="#1c1a15" data-title="VANILLA<br>BLISS" data-subtitle="Pure Madagascar vanilla, slow-churned to creamy perfection. The quintessential flavor elevated to extraordinary heights." onclick="switchHeroFlavor(this)">
                            <img src="images/vanilla-hero.png" alt="Vanilla" style="border-radius:10px">
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
        // Render remaining categories
        foreach ($productsByCategory as $category => $items): 
            if (empty($items)) continue;
            if (strtolower($category) === 'size' || strtolower($category) === 'topping') continue;
        ?>
        <div class="category">
            <h2><?= htmlspecialchars(ucfirst($category)) ?>s</h2>
            <div class="products">
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
                        <?php if (!empty($review['comment'])): ?>
                        <p class="review-text">"<?= htmlspecialchars($review['comment']) ?>"</p>
                        <?php endif; ?>
                        <div class="review-author">- <?= htmlspecialchars($review['customer_name']) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="slider-controls" id="sliderDots"></div>
        <div style="text-align: center; margin-top: 50px;"><a href="reviews.php" style="color: var(--accent-color); text-decoration: none; font-weight: 800; font-size: 1.1rem; transition: var(--transition);" class="view-all-reviews">Discover All Stories →</a></div>
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

            // Change hero background color smoothly via CSS variables
            // This ensures theme switching works immediately even after flavor changes
            heroSection.style.setProperty('--hero-flavor-light', thumb.dataset.bg);
            heroSection.style.setProperty('--hero-flavor-dark', thumb.dataset.bgDark);

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

        // Promo Code Alert Script removed

        function copyPromoCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Promo code ' + code + ' copied to clipboard',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    background: 'var(--bg-color)',
                    color: 'var(--primary-text)'
                });
            });
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

        function confirmDeletePreview(id) {
            Swal.fire({
                title: 'Delete Review?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2c296d',
                cancelButtonColor: '#6b6b8d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'reviews.php?delete=' + id + '&redirect=index';
                }
            })
        }

        // Voucher 3D Tilt Effect
        document.addEventListener('DOMContentLoaded', () => {
            const voucher = document.getElementById('offerVoucher');
            if (!voucher) return;

            const wrapper = voucher.parentElement;
            
            wrapper.addEventListener('mousemove', (e) => {
                const rect = wrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 15;
                const rotateY = (centerX - x) / 15;
                
                voucher.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            
            wrapper.addEventListener('mouseleave', () => {
                voucher.style.transform = `perspective(1000px) rotateX(0deg) rotateY(0deg)`;
            });
        });
    </script>
</body>
</html>
