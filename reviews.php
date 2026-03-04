<?php
session_start();
require_once 'config/database.php';

$isLoggedIn = isset($_SESSION['user_id']);

// Initialize database connection
$db = Database::getInstance()->getConnection();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    $rating = (float)($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    $product_name = trim($_POST['product_name'] ?? '');
    $customer_name = $_SESSION['user_name'] ?? 'Guest';
    $customer_id = $_SESSION['user_id'] ?? null;
    $review_id = $_POST['review_id'] ?? null;
    $action = $_POST['action'] ?? 'add';
    
    if ($rating >= 1 && $rating <= 5) {
        try {
            if ($action === 'update' && !empty($review_id)) {
                // Update existing review
                $stmt = $db->prepare("UPDATE reviews SET rating = ?, comment = ?, product_name = ? WHERE id = ? AND customer_id = ?");
                $stmt->execute([$rating, $comment, $product_name, $review_id, $customer_id]);
                header("Location: reviews.php?success=updated");
            } else {
                // Insert new review
                $stmt = $db->prepare("INSERT INTO reviews (customer_id, customer_name, rating, comment, product_name, status) VALUES (?, ?, ?, ?, ?, 'approved')");
                $stmt->execute([$customer_id, $customer_name, $rating, $comment, $product_name]);
                header("Location: reviews.php?success=1");
            }
            exit;
        } catch (Exception $e) {
            $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert error'>Please provide a rating between 1 and 5.</div>";
    }
}

// Handle Delete Review
if (isset($_GET['delete']) && $isLoggedIn) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ? AND customer_id = ?");
        $stmt->execute([$delete_id, $_SESSION['user_id']]);
        $redirectTo = (isset($_GET['redirect']) && $_GET['redirect'] === 'index') ? 'index.php' : 'reviews.php';
        header("Location: $redirectTo?success=deleted");
        exit;
    } catch (Exception $e) {
        $message = "<div class='alert error'>Error deleting review: " . $e->getMessage() . "</div>";
    }
}

// Handle Edit Mode Data Fetching
$edit_data = null;
if (isset($_GET['edit_id']) && $isLoggedIn) {
    try {
        $stmt = $db->prepare("SELECT * FROM reviews WHERE id = ? AND customer_id = ?");
        $stmt->execute([$_GET['edit_id'], $_SESSION['user_id']]);
        $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Fetch products for dropdown
try {
    $stmt = $db->query("SELECT name FROM products WHERE category = 'flavor' ORDER BY name ASC");
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allProducts = [];
}

// Fetch reviews from database
try {
    $stmt = $db->query("SELECT * FROM reviews WHERE status = 'approved' ORDER BY created_at DESC");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reviews)) {
        // Fallback or empty state
        $averageRating = 0;
    } else {
        // Calculate average rating
        $totalRating = array_sum(array_column($reviews, 'rating'));
        $averageRating = round($totalRating / count($reviews), 1);
    }
} catch (Exception $e) {
    $reviews = [];
    $averageRating = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Scoops</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@400;500;600;700&family=Slabo+27px&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="public/index.css">
    <style>
        :root {
            --primary-color: #2c296d;
            --accent-color: #6c5dfc;
            --bg-light: #f7f3ff;
        }
        

        
        .reviews-page-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 140px 20px 80px !important;
            display: block;
        }
        
        .reviews-container {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.1);
            padding: 40px;
            border: 2px solid var(--card-border);
            transition: var(--transition);
        }

        [data-theme="dark"] .reviews-container {
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5);
        }
        
        .reviews-header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .reviews-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--primary-text) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }

        [data-theme="dark"] .reviews-title {
            background: linear-gradient(135deg, var(--accent-color) 0%, #ffffff 100%);
            -webkit-background-clip: text;
        }
        
        .average-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        
        .rating-stars {
            color: #ffd700;
            font-size: 0.8rem;
        }
        
        .rating-text {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 20px !important;
            margin-top: 30px !important;
        }
        
        .review-card {
            background: var(--white);
            border-radius: 20px !important;
            padding: 24px !important;
            transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            border: 1px solid var(--card-border) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        [data-theme="dark"] .review-card {
            background: rgba(26, 25, 20, 0.6) !important;
            border-color: rgba(167, 139, 250, 0.15) !important;
        }
        
        .review-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(108, 93, 252, 0.1);
            border-color: var(--accent-color) !important;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px !important;
        }
        
        .customer-info {
            flex: 1;
        }
        
        .customer-name {
            font-weight: 600 !important;
            color: var(--primary-color);
            font-size: 0.8rem !important;
            margin-bottom: 0px !important;
        }
        
        .review-date {
            color: #718096;
            font-size: 0.6rem !important;
        }
        
        .review-rating {
            color: #ffd700;
            font-size: 0.70rem !important;
        }
        
        .review-product {
            background: rgba(102, 126, 234, 0.1) !important;
            color: var(--primary-color) !important;
            padding: 2px 5px !important;
            border-radius: 8px !important;
            font-size: 0.6rem !important;
            font-weight: 600 !important;
            margin-bottom: 4px !important;
            display: inline-block;
        }
        
        .review-comment {
            color: #4a5568 !important;
            line-height: 1.2 !important;
            font-size: 0.7rem !important;
            margin: 0 !important;
        }
        
            .review-actions {
            display: flex;
            gap: 8px;
            margin-top: 5px;
        }

        .review-action-btn {
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: var(--text-muted);
        }

        .action-edit:hover { background: rgba(108, 93, 252, 0.1); color: var(--accent-color); }
        .action-delete:hover { background: rgba(245, 101, 101, 0.1); color: #c53030; }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--btn-bg, #2c296d);
            color: var(--white, white);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(44, 41, 109, 0.1);
            margin-bottom: 15px;
        }
        
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(44, 41, 109, 0.3);
            color: white;
            text-decoration: none;
            filter: brightness(1.1);
        }

        .review-form-container {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid var(--card-border);
            transition: var(--transition);
        }

        [data-theme="dark"] .review-form-container {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(167, 139, 250, 0.2);
        }

        .review-form-container h3 {
            color: var(--primary-text);
            margin-top: 0;
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-text);
            font-weight: 700;
            font-size: 0.95rem;
        }

        .form-input, .form-textarea, select.form-input {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            background: var(--bg-color);
            color: var(--primary-text);
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-textarea:focus, select.form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(108, 93, 252, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 50px;
        }

        .submit-btn {
            background: var(--btn-bg);
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 50px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.19, 1, 0.22, 1);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(108, 93, 252, 0.3);
            filter: brightness(1.1);
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert.success {
            background: rgba(72, 187, 120, 0.1);
            color: #2f855a;
            border: 1px solid rgba(72, 187, 120, 0.2);
        }

        .alert.error {
            background: rgba(245, 101, 101, 0.1);
            color: #c53030;
            border: 1px solid rgba(245, 101, 101, 0.2);
        }

        .login-prompt {
            text-align: center;
            padding: 10px;
            border-radius: 15px;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 0.8rem;
        }

        .login-prompt a {
            color: var(--accent-color);
            font-weight: 700;
            text-decoration: none;
        }

        .login-prompt a:hover {
            text-decoration: underline;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2rem;
            color: #cbd5e0;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffd700;
        }
        
        @media (max-width: 768px) {
            .reviews-grid {
                grid-template-columns: 1fr;
            }
            
            .reviews-title {
                font-size: 2rem;
            }
            
            .reviews-container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="reviews-page-wrapper">
        <div class="reviews-container">
            <a href="index.php" class="back-button">
                <span>←</span> Back to Products
            </a>
            
            <div class="reviews-header">
                <h1 class="reviews-title">Customer Reviews</h1>
                <div class="average-rating">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= $averageRating ? '★' : '☆' ?>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-text"><?= $averageRating ?> out of 5</span>
                </div>
                <p style="color: #718096; margin: 0; font-size: 0.7rem;">Based on <?= count($reviews) ?> reviews</p>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] == '1'): ?>
                    <div class="alert success">Thank you! Your review has been submitted successfully.</div>
                <?php elseif ($_GET['success'] == 'updated'): ?>
                    <div class="alert success">Your review has been updated successfully.</div>
                <?php elseif ($_GET['success'] == 'deleted'): ?>
                    <div class="alert success">Review deleted successfully.</div>
                <?php endif; ?>
            <?php endif; ?>
            <?= $message ?>

            <?php if ($isLoggedIn): ?>
            <div class="review-form-container" id="review-form">
                <h3><?= $edit_data ? 'Edit Your Review' : 'Write a Review' ?></h3>
                <form method="POST" action="reviews.php">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'update' : 'add' ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="review_id" value="<?= $edit_data['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <div class="star-rating">
                            <?php 
                            $current_rating = $edit_data ? (int)$edit_data['rating'] : 5;
                            for ($i = 5; $i >= 1; $i--): 
                            ?>
                                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $current_rating == $i ? 'checked' : '' ?> />
                                <label for="star<?= $i ?>" title="<?= $i ?> stars">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_name" class="form-label">Product Name (Optional)</label>
                        <div style="position: relative;">
                            <select id="product_name" name="product_name" class="form-input" style="cursor: pointer;">
                                <option value="">Select Flavor</option>
                                <?php foreach ($allProducts as $product): ?>
                                    <option value="<?= htmlspecialchars($product['name']) ?>" <?= ($edit_data && $edit_data['product_name'] == $product['name']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($product['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #6b6b8d;">▼</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment" class="form-label">Your Review (Optional)</label>
                        <textarea id="comment" name="comment" class="form-textarea" placeholder="Share your experience with us (optional)..."><?= $edit_data ? htmlspecialchars($edit_data['comment']) : '' ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="submit" class="submit-btn"><?= $edit_data ? 'Update Review' : 'Submit Review' ?></button>
                        <?php if ($edit_data): ?>
                            <a href="reviews.php" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: none;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="login-prompt">
                Please <a href="login.php?redirect=reviews.php">log in</a> to write a review.
            </div>
            <?php endif; ?>
            
            <div class="reviews-grid">
                <?php 
                $showAll = isset($_GET['all']) && $_GET['all'] == '1';
                $displayReviews = $showAll ? $reviews : array_slice($reviews, 0, 6);
                foreach ($displayReviews as $review): 
                ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="customer-info">
                            <div class="customer-name"><?= htmlspecialchars($review['customer_name']) ?></div>
                            <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                        </div>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $review['rating'] ? '★' : '☆' ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($review['product_name'])): ?>
                    <div class="review-product"><?= htmlspecialchars($review['product_name']) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($review['comment'])): ?>
                    <div class="review-comment">
                        "<?= htmlspecialchars($review['comment']) ?>"
                    </div>
                    <?php endif; ?>

                    <?php if ($isLoggedIn && $review['customer_id'] === $_SESSION['user_id']): ?>
                    <div class="review-actions">
                        <a href="reviews.php?edit_id=<?= $review['id'] ?>#review-form" class="review-action-btn action-edit" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <button onclick="confirmDelete(<?= $review['id'] ?>)" class="review-action-btn action-delete" title="Delete">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($reviews) > 6): ?>
                <div style="text-align: center; margin-top: 40px;">
                    <?php if (!$showAll): ?>
                        <a href="reviews.php?all=1" class="submit-btn" style="text-decoration: none;">View All Reviews</a>
                    <?php else: ?>
                        <a href="reviews.php" class="submit-btn" style="text-decoration: none; background: #6b6b8d; box-shadow: none;">Show Less</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function confirmDelete(id) {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            
            Swal.fire({
                title: 'Delete Review?',
                text: "This beautiful memory will be removed forever.",
                icon: 'warning',
                iconColor: '#ef4444',
                showCancelButton: true,
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Keep it',
                background: isDark ? '#1e1e2f' : '#ffffff',
                color: isDark ? '#f0f0f5' : '#2c296d',
                customClass: {
                    popup: 'premium-swal-popup',
                    title: 'premium-swal-title',
                    htmlContainer: 'premium-swal-text',
                    actions: 'premium-swal-actions',
                    confirmButton: 'premium-swal-confirm',
                    cancelButton: 'premium-swal-cancel'
                },
                buttonsStyling: false,
                showClass: {
                    popup: 'animate__animated animate__fadeInUp animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutDown animate__faster'
                },
                backdrop: `
                    rgba(44, 41, 109, 0.4)
                    left top
                    no-repeat
                `
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Your review has been gracefully removed.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        background: isDark ? '#1e1e2f' : '#ffffff',
                        color: isDark ? '#f0f0f5' : '#2c296d',
                        customClass: {
                            popup: 'premium-swal-popup'
                        }
                    }).then(() => {
                        window.location.href = 'reviews.php?delete=' + id;
                    });
                }
            })
        }
    </script>
    
    <style>
        .premium-swal-popup {
            border-radius: 24px !important;
            padding: 2rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(108, 93, 252, 0.1) !important;
        }
        .premium-swal-title {
            font-family: 'Slabo 27px', serif !important;
            font-size: 1.8rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.5rem !important;
        }
        .premium-swal-text {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1rem !important;
            opacity: 0.8 !important;
            margin-bottom: 1.5rem !important;
        }
        .premium-swal-actions {
            display: flex !important;
            flex-direction: row !important;
            gap: 12px !important;
            justify-content: center !important;
            width: 100% !important;
        }
        .premium-swal-confirm {
            background: linear-gradient(135deg, #ef4444 0%, #db2777 100%) !important;
            color: white !important;
            border: none !important;
            padding: 12px 30px !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            margin: 0 5px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3) !important;
        }
        .premium-swal-confirm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 20px 25px -5px rgba(239, 68, 68, 0.4) !important;
        }
        .premium-swal-cancel {
            background: rgba(108, 93, 252, 0.1) !important;
            color: var(--accent-color) !important;
            border: 1px solid rgba(108, 93, 252, 0.2) !important;
            padding: 12px 30px !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            font-size: 0.95rem !important;
            margin: 0 5px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }
        .premium-swal-cancel:hover {
            background: rgba(108, 93, 252, 0.2) !important;
            transform: translateY(-2px) !important;
        }
    </style>
</body>
</html>