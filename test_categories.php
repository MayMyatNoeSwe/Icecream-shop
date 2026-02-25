<?php
session_start();
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM products WHERE quantity > 0 ORDER BY category, name");
    $products = $stmt->fetchAll();
    
    // Group products by category
    $productsByCategory = [];
    foreach ($products as $product) {
        $productsByCategory[$product['category']][] = $product;
    }
    
    // Sort each category's items to put featured products at the top
    foreach ($productsByCategory as $category => &$items) {
        usort($items, function($a, $b) {
            $aFeatured = isset($a['is_featured']) ? (int)$a['is_featured'] : 0;
            $bFeatured = isset($b['is_featured']) ? (int)$b['is_featured'] : 0;
            return $bFeatured <=> $aFeatured;
        });
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Category Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .category { margin-bottom: 30px; background: white; padding: 20px; border-radius: 10px; }
        .category h2 { color: #2c296d; border-bottom: 2px solid #6c5dfc; padding-bottom: 10px; }
        .product { padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 3px solid #6c5dfc; }
    </style>
</head>
<body>
    <h1>Product Categories Test</h1>
    <?php foreach ($productsByCategory as $category => $items): ?>
    <div class="category">
        <h2><?= strtoupper($category) ?> (<?= count($items) ?> items)</h2>
        <?php foreach ($items as $product): ?>
        <div class="product">
            <strong><?= htmlspecialchars($product['name']) ?></strong> 
            - Price: <?= number_format($product['price'], 0) ?> MMK
            - Stock: <?= $product['quantity'] ?>
            <?php if ($product['discount_percentage'] > 0): ?>
                <span style="color: red;">[<?= $product['discount_percentage'] ?>% OFF]</span>
            <?php endif; ?>
            <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                <span style="color: gold;">[★ FEATURED]</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</body>
</html>
