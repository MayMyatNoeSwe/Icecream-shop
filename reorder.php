<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Fetch order items
        $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        
        if ($items) {
            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Fetch all products for name-to-id mapping
            $stmtAll = $db->query("SELECT id, name, category FROM products");
            $allProducts = $stmtAll->fetchAll();
            $sizesMap = [];
            $toppingsMap = [];
            foreach ($allProducts as $p) {
                if ($p['category'] === 'size') $sizesMap[$p['name']] = $p['id'];
                if ($p['category'] === 'topping') $toppingsMap[$p['name']] = $p['id'];
            }

            $addedCount = 0;
            $skippedCount = 0;
            
            foreach ($items as $item) {
                // Fetch current product info (product_id in order_items is the flavor_id for custom orders)
                $stmtProd = $db->prepare("SELECT * FROM products WHERE id = ?");
                $stmtProd->execute([$item['product_id']]);
                $product = $stmtProd->fetch();
                
                if ($product && $product['quantity'] > 0) {
                    $requestedQty = $item['quantity'];
                    $availableQty = $product['quantity'];
                    
                    // Parse customization from item name if it exists
                    $itemName = $item['product_name'];
                    $isCustom = false;
                    $sizeId = null;
                    $sizeName = null;
                    $toppingIds = [];
                    $toppingsDetails = [];
                    
                    // Pattern: "Flavor (Size) + Topping1, Topping2"
                    if (preg_match('/ \((.*?)\)/', $itemName, $sizeMatch)) {
                        $sizeName = $sizeMatch[1];
                        if (isset($sizesMap[$sizeName])) {
                            $isCustom = true;
                            $sizeId = $sizesMap[$sizeName];
                            
                            // Check for toppings
                            $parts = explode(' + ', $itemName);
                            if (isset($parts[1])) {
                                $toppingNames = explode(', ', $parts[1]);
                                foreach ($toppingNames as $tName) {
                                    $tName = trim($tName);
                                    if (isset($toppingsMap[$tName])) {
                                        $tId = $toppingsMap[$tName];
                                        $toppingIds[] = $tId;
                                        $toppingsDetails[] = [
                                            'id' => $tId,
                                            'name' => $tName
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    // Check if already in cart (same customization)
                    $found = false;
                    foreach ($_SESSION['cart'] as &$cartItem) {
                        $sameCustom = ($isCustom && isset($cartItem['custom']) && 
                                      $cartItem['flavor_id'] === $item['product_id'] && 
                                      $cartItem['size_id'] === $sizeId && 
                                      $cartItem['toppings'] === $toppingIds);
                        
                        // For regular items
                        $sameRegular = (!$isCustom && !isset($cartItem['custom']) && $cartItem['id'] === $item['product_id']);

                        if ($sameCustom || $sameRegular) {
                            $currentInCart = $cartItem['cart_quantity'];
                            $newQty = min($currentInCart + $requestedQty, $availableQty);
                            $cartItem['cart_quantity'] = $newQty;
                            $found = true;
                            $addedCount++;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $price = $item['price']; // Use the historical price as a starting point
                        $finalQty = min($requestedQty, $availableQty);
                        
                        $cartEntry = [
                            'id' => $isCustom ? 'custom_' . uniqid() : $product['id'],
                            'name' => $isCustom ? explode(' (', $item['product_name'])[0] : $item['product_name'],
                            'price' => floatval($item['price']),
                            'original_price' => floatval($item['original_price'] ?? $item['price']),
                            'image_url' => $product['image_url'],
                            'cart_quantity' => $finalQty,
                            'max_quantity' => $availableQty,
                            'has_discount' => (isset($item['discount_applied']) && $item['discount_applied'] > 0),
                            'discount_percentage' => floatval($item['discount_applied'] ?? 0)
                        ];

                        if ($isCustom) {
                            $cartEntry['custom'] = true;
                            $cartEntry['flavor_id'] = $item['product_id'];
                            $cartEntry['size_id'] = $sizeId;
                            $cartEntry['size_name'] = $sizeName;
                            $cartEntry['toppings'] = $toppingIds;
                            $cartEntry['toppings_details'] = $toppingsDetails;
                            $cartEntry['description'] = 'Custom ice cream order';
                            $cartEntry['is_reorder'] = true;
                        }

                        $_SESSION['cart'][] = $cartEntry;
                        $addedCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }
            
            if ($addedCount > 0) {
                $msg = $skippedCount > 0 ? "reorder_partial" : "success";
                header('Location: cart.php?reorder=' . $msg);
            } else {
                header('Location: orders.php?reorder=failed_stock');
            }
            exit;
        }
    } catch (Exception $e) {
        header('Location: orders.php?reorder=error');
        exit;
    }
}

header('Location: orders.php');
exit;
