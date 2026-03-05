<?php
session_name('SCOOPS_ADMIN_SESSION');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Validate status
    if (!in_array($status, ['pending', 'completed', 'cancelled'])) {
        header('Location: orders.php?error=invalid_status');
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        
        header('Location: orders.php?success=status_updated');
        exit;
    } catch (Exception $e) {
        header('Location: orders.php?error=update_failed');
        exit;
    }
} else {
    header('Location: orders.php');
    exit;
}
?>
