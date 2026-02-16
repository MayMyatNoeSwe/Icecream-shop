<?php
session_start();
require_once 'config/database.php';

// Initialize database connection
$db = Database::getInstance()->getConnection();

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Scoops</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c296d;
            --accent-color: #6c5dfc;
            --bg-light: #f7f3ff;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .reviews-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            margin-top: 40px;
        }
        
        .reviews-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .reviews-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }
        
        .average-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .rating-stars {
            color: #ffd700;
            font-size: 1.5rem;
        }
        
        .rating-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .review-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .customer-info {
            flex: 1;
        }
        
        .customer-name {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .review-date {
            color: rgba(102, 126, 234, 0.8);
            font-size: 0.9rem;
        }
        
        .review-rating {
            color: #ffd700;
            font-size: 1.2rem;
        }
        
        .review-product {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .review-comment {
            color: #764ba2;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            color: #667eea;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid rgba(102, 126, 234, 0.2);
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            text-decoration: none;
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
    <div class="container">
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
                <p style="color: rgba(102, 126, 234, 0.8); margin: 0;">Based on <?= count($reviews) ?> reviews</p>
            </div>
            
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
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
                    
                    <div class="review-product"><?= htmlspecialchars($review['product_name']) ?></div>
                    
                    <div class="review-comment">
                        "<?= htmlspecialchars($review['comment']) ?>"
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>