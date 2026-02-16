<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM products ORDER BY category, name");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Product Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🔧 Admin Panel</h1>
            <nav>
                <a href="../index.php">← Back to Shop</a>
                <a href="index.php">📦 Products</a>
                <a href="add_product.php">➕ Add Product</a>
                <a href="orders.php">📋 All Orders</a>
                <a href="logout.php" style="color: #ef4444;">🚪 Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="actions">
            <a href="add_product.php" class="btn">+ Add New Product</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['description']) ?></td>
                    <td><?= number_format($product['price'], 0) ?> MMK</td>
                    <td>
                        <span class="category-badge badge-<?= $product['category'] ?>">
                            <?= ucfirst($product['category']) ?>
                        </span>
                    </td>
                    <td><?= $product['quantity'] ?></td>
                    <td>
                        <button class="details-btn" onclick="showProductDetails('<?= $product['id'] ?>')">👁️ Details</button>
                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="edit-btn">Edit</a>
                        <form method="POST" action="delete_product.php" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            <button type="submit" class="delete-btn" onclick="return confirm('Delete this product?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Product Details Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Product Details</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="product-details-grid">
                    <div class="product-image">
                        <img id="modalImage" src="" alt="Product Image" style="width: 100%; max-width: 300px; border-radius: 12px; object-fit: cover;">
                    </div>
                    <div class="product-info">
                        <div class="detail-row">
                            <strong>Name:</strong>
                            <span id="modalName"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Description:</strong>
                            <span id="modalDescription"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Price:</strong>
                            <span id="modalPrice"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Category:</strong>
                            <span id="modalCategory"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Stock Quantity:</strong>
                            <span id="modalQuantity"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Product ID:</strong>
                            <span id="modalId"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Created:</strong>
                            <span id="modalCreated"></span>
                        </div>
                        <div class="detail-row">
                            <strong>Last Updated:</strong>
                            <span id="modalUpdated"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Product data for modal
        const products = <?= json_encode($products) ?>;
        
        function showProductDetails(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;
            
            document.getElementById('modalTitle').textContent = product.name + ' - Details';
            document.getElementById('modalName').textContent = product.name;
            document.getElementById('modalDescription').textContent = product.description || 'No description';
            document.getElementById('modalPrice').textContent = new Intl.NumberFormat().format(product.price) + ' MMK';
            document.getElementById('modalCategory').innerHTML = `<span class="category-badge badge-${product.category}">${product.category.charAt(0).toUpperCase() + product.category.slice(1)}</span>`;
            document.getElementById('modalQuantity').textContent = product.quantity;
            document.getElementById('modalId').textContent = product.id;
            document.getElementById('modalCreated').textContent = new Date(product.created_at).toLocaleString();
            document.getElementById('modalUpdated').textContent = new Date(product.updated_at).toLocaleString();
            
            // Set image
            let imageUrl;
            if (product.image_url) {
                // Adjust path for admin subfolder
                imageUrl = product.image_url.startsWith('images/') 
                    ? '../' + product.image_url 
                    : product.image_url;
            } else {
                // Default fallback image
                imageUrl = 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop';
            }
            
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalImage').alt = product.name;
            
            // Add error handling for images
            document.getElementById('modalImage').onerror = function() {
                this.src = 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=400&h=300&fit=crop';
            };
            
            document.getElementById('productModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
