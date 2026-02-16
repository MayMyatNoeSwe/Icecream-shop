<?php
header('Content-Type: application/json');
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false, 
            'message' => 'This email is already subscribed to the Scoop Club!'
        ]);
        exit;
    }
    
    // Add new subscriber
    $stmt = $db->prepare("INSERT INTO subscribers (email) VALUES (?)");
    $stmt->execute([$email]);
    
    // Respond with success and discount code
    echo json_encode([
        'success' => true,
        'message' => 'Welcome to the club! Use code SCOOP10 for 10% off your next order.',
        'discount_code' => 'SCOOP10'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
