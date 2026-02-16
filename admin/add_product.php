<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = $_POST['category'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
    $discount_start_date = $_POST['discount_start_date'] ?? null;
    $discount_end_date = $_POST['discount_end_date'] ?? null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Convert empty dates to null
    if (empty($discount_start_date)) $discount_start_date = null;
    if (empty($discount_end_date)) $discount_end_date = null;
    
    if (empty($name)) {
        $error = 'Product name is required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif ($quantity < 0) {
        $error = 'Quantity cannot be negative';
    } elseif (!in_array($category, ['flavor', 'size', 'topping'])) {
        $error = 'Invalid category';
    } elseif ($discount_percentage < 0 || $discount_percentage > 100) {
        $error = 'Discount percentage must be between 0 and 100';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            $id = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO products (id, name, description, price, category, quantity, image_url, discount_percentage, discount_start_date, discount_end_date, is_featured) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $name, $description, $price, $category, $quantity, $image_url, $discount_percentage, $discount_start_date, $discount_end_date, $is_featured]);
            $success = true;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>➕ Add New Product</h1>
    </header>
    
    <div class="container">
        <a href="index.php" class="back-link">← Back to Products</a>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    Product added successfully! <a href="index.php">View all products</a>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (MMK) *</label>
                    <input type="number" id="price" name="price" step="1" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="flavor">Flavor</option>
                        <option value="size">Size</option>
                        <option value="topping">Topping</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Initial Stock Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="0" value="0" required>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL (Optional)</label>
                    <input type="text" id="image_url" name="image_url" placeholder="https://example.com/image.jpg or images/filename.jpg">
                    <small>Optional: Enter a URL for the product image</small>
                </div>
                
                <!-- Discount Section -->
                <div class="discount-section">
                    <h3 style="color: #2d3748; margin: 20px 0 15px 0; font-size: 18px;">🏷️ Discount Settings (Optional)</h3>
                    
                    <div class="form-group">
                        <label for="discount_percentage">Discount Percentage (%)</label>
                        <input type="number" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.01" value="0" placeholder="0">
                        <small>Enter discount percentage (0-100). Leave 0 for no discount.</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="discount_start_date">Discount Start Date</label>
                            <input type="datetime-local" id="discount_start_date" name="discount_start_date">
                            <small>When the discount becomes active</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="discount_end_date">Discount End Date</label>
                            <input type="datetime-local" id="discount_end_date" name="discount_end_date">
                            <small>When the discount expires</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_featured" name="is_featured">
                            <span class="checkmark"></span>
                            Mark as Featured Product
                        </label>
                        <small>Featured products appear prominently on the homepage</small>
                    </div>
                </div>
                
                <button type="submit" class="btn">Add Product</button>
            </form>
        </div>
    </div>
</body>
</html>
